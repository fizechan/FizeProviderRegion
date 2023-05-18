<?php

namespace Fize\Provider\Region\Handler;

use DOMDocument;
use DOMXPath;
use Fize\Provider\Region\RegionHandler;
use Fize\Provider\Region\RegionItem;
use SQLite3;

/**
 * 中华人民共和国民政部
 */
class MCA extends RegionHandler
{

    /**
     * @var SQLite3 数据库
     */
    private $db;


    /**
     *  构造
     * @param array $config 配置
     */
    public function __construct(array $config = null)
    {
        parent::__construct($config);
        $this->db = new SQLite3(dirname(dirname(__DIR__)) . "/data/MCA.sqlite3", SQLITE3_OPEN_READWRITE);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->db->close();
    }

    /**
     * 根据编码获取完整信息
     * 返回一个长度为3的数组，依次是省市区，为null表示没有指定该数据
     * @param int $id 编码
     * @return RegionItem[]|null[]
     */
    public function get(int $id): array
    {
        $item1 = $this->getOne($id);
        if (is_null($item1)) {
            return [null, null, null];
        }
        if (empty($item1->parentId)) {
            return [null, null, $item1];
        }
        $item2 = $this->getOne($item1->parentId);
        if (empty($item2->parentId)) {
            return [null, $item2, $item1];
        }
        $item3 = $this->getOne($item2->parentId);
        return [$item3, $item2, $item1];
    }

    /**
     * 获取省列表
     * @return RegionItem[]
     */
    public function getProvinces(): array
    {
        return $this->getList(0);
    }

    /**
     * 根据省编码返回市列表
     * @param int $provinceId 省编码
     * @return RegionItem[]
     */
    public function getCitys(int $provinceId): array
    {
        return $this->getList($provinceId);
    }

    /**
     * 根据市编码返回区列表
     * @param int $cityId 市编码
     * @return RegionItem[]
     */
    public function getAreas(int $cityId): array
    {
        return $this->getList($cityId);
    }

    /**
     * 根据ID返回项
     * @param int $id ID
     * @return RegionItem|null 未找到返回null
     */
    private function getOne(int $id)
    {
        $row = $this->db->querySingle("SELECT * FROM region WHERE id = {$id}", true);
        if (empty($row)) {
            return null;
        }
        $item = new RegionItem();
        $item->id = $row['id'];
        $item->parentId = $row['pid'];
        $item->name = $row['name'];
        $item->level = $row['level'];
        return $item;
    }

    /**
     * 获取指定父级下的地区列表
     * @param int $parentid 父级ID
     * @return RegionItem[]
     */
    private function getList(int $parentid): array
    {
        $result = $this->db->query("SELECT * FROM region WHERE pid = {$parentid} ORDER BY sort ASC");
        $items = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $item = new RegionItem();
            $item->id = $row['id'];
            $item->parentId = $row['pid'];
            $item->name = $row['name'];
            $item->level = $row['level'];
            $items[] = $item;
        }
        return $items;
    }

    /**
     * 更新数据
     * @param int $adjust 调整格式：0-不调整；1-直辖市；2-直辖市+港澳台；
     */
    public function update(int $adjust = 0)
    {
        libxml_use_internal_errors(true);
        $doc = new DomDocument();
        $doc->preserveWhiteSpace = false;
        $url = 'https://www.mca.gov.cn/article/sj/xzqh/2022/202201xzqh.html';
        $html = file_get_contents($url);
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $trs = $xpath->query('//tr');
        $rows = [];
        $cur_pid1 = 0;  // 省
        $cur_pid2 = 0;  // 市
        $cur_sort1 = 0;  // 省
        $cur_sort2 = 0;  // 市
        $cur_sort3 = 0;  // 区
        foreach ($trs as $tr) {
            $tds = $xpath->query('./td', $tr);
            if ($tds->length != 9) {
                continue;
            }
            $id = trim($tds->item(1)->nodeValue);
            if (!is_numeric($id)) {
                continue;
            }
            $name = $tds->item(2)->nodeValue;
            $name = htmlentities($name);
            $name = str_replace('&nbsp;', ' ', $name);
            $name = trim($name);
            $spans = $xpath->query("./span", $tds->item(2));
            $tr_type = 1;  // 省
            if ($spans->length > 0) {
                $text = htmlentities(trim($spans->item(0)->nodeValue));
                if ($text == '&nbsp;') {
                    $tr_type = 2;
                } elseif ($text == '&nbsp;&nbsp;') {
                    $tr_type = 3;
                }
            }
            if ($tr_type == 3) {  // 区
                $cur_sort3 += 1;
                $row = [
                    'id'    => $id,
                    'pid'   => $cur_pid2,
                    'name'  => $name,
                    'level' => 3,
                    'sort'  => $cur_sort3
                ];
                $rows[] = $row;
            } elseif ($tr_type == 2) {  // 市
                $cur_sort2 += 1;
                $cur_sort3 = 0;
                $row = [
                    'id'    => $id,
                    'pid'   => $cur_pid1,
                    'name'  => $name,
                    'level' => 2,
                    'sort'  => $cur_sort2
                ];
                $rows[] = $row;
                $cur_pid2 = $id;
            } else {  // 省
                $cur_sort1 += 1;
                $cur_sort2 = 0;
                $cur_sort3 = 0;
                $row = [
                    'id'    => $id,
                    'pid'   => 0,
                    'name'  => $name,
                    'level' => 1,
                    'sort'  => $cur_sort1
                ];
                $rows[] = $row;
                $cur_pid1 = $id;
                $cur_pid2 = $id;  // 直辖市兼容
                if ($adjust >= 1) {  // 直辖市调整
                    if (in_array($id, ['110000', '	120000', '310000', '500000'])) {
                        // 市
                        $cur_sort2 += 1;
                        $id2 = substr($name, 0, 2) . '0100';
                        $row = [
                            'id'    => $id2,
                            'pid'   => $cur_pid1,
                            'name'  => $name,
                            'level' => 2,
                            'sort'  => $cur_sort2
                        ];
                        $rows[] = $row;
                        $cur_pid2 = $id2;
                    }
                }
                if ($adjust >= 2) {  // 港澳台调整
                    if (in_array($id, ['710000', '	810000', '820000'])) {
                        // 市
                        $cur_sort2 += 1;
                        $id2 = substr($name, 0, 2) . '0100';
                        $row = [
                            'id'    => $id2,
                            'pid'   => $cur_pid1,
                            'name'  => $name,
                            'level' => 2,
                            'sort'  => $cur_sort2
                        ];
                        $rows[] = $row;
                        $cur_pid2 = $id2;
                        // 区
                        $cur_sort3 += 1;
                        $id3 = substr($name, 0, 2) . '0101';
                        $row = [
                            'id'    => $id3,
                            'pid'   => $cur_pid2,
                            'name'  => $name,
                            'level' => 3,
                            'sort'  => $cur_sort3
                        ];
                        $rows[] = $row;
                    }
                }
            }
        }
        $this->db->exec('BEGIN TRANSACTION');
        $this->db->exec('DELETE FROM region');
        foreach ($rows as $row) {
            $sql = "INSERT INTO region (id, pid, name, level, sort) VALUES ({$row['id']}, {$row['pid']}, '{$row['name']}', '{$row['level']}', {$row['sort']})";
            $this->db->exec($sql);
        }
        $this->db->exec('COMMIT');
    }
}
