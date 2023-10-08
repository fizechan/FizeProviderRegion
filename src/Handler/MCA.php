<?php

namespace Fize\Provider\Region\Handler;

use DOMDocument;
use DOMXPath;
use Fize\Provider\Region\RegionHandler;
use Fize\Provider\Region\RegionItem;
use RuntimeException;
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
        $this->db = new SQLite3(dirname(__DIR__, 2) . "/data/MCA.sqlite3", SQLITE3_OPEN_READONLY);
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
        return $this->gets(0);
    }

    /**
     * 根据省编码返回市列表
     * @param int $provinceId 省编码
     * @return RegionItem[]
     */
    public function getCitys(int $provinceId): array
    {
        return $this->gets($provinceId);
    }

    /**
     * 根据市编码返回区列表
     * @param int $cityId 市编码
     * @return RegionItem[]
     */
    public function getCountys(int $cityId): array
    {
        return $this->gets($cityId);
    }

    /**
     * 获取完整名称
     * @param int    $id        编码
     * @param string $separator 间隔符
     * @param int    $adjust    调整方式：0-不调整；1-去除【市辖区、县、直辖县级】；
     * @return string
     */
    public function getFullName(int $id, string $separator = '', int $adjust = 0): string
    {
        $county = $this->db->querySingle("SELECT * FROM region WHERE id = {$id}", true);
        if (empty($county)) {
            return '';
        }
        $full_name = '';
        $bad_strs = ['市辖区', '县', '直辖县级'];
        if (!in_array($county['name'], $bad_strs) || $adjust == 0) {
            $full_name = $county['name'];
        }
        $city = $this->db->querySingle("SELECT * FROM region WHERE id = {$county['pid']}", true);
        if (!in_array($city['name'], $bad_strs) || $adjust == 0) {
            $full_name = $city['name'] . $separator . $full_name;
        }
        $province = $this->db->querySingle("SELECT * FROM region WHERE id = {$city['pid']}", true);
        if (!in_array($province['name'], $bad_strs) || $adjust == 0) {
            $full_name = $province['name'] . $separator . $full_name;
        }
        return $full_name;
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
        $item->pid = $row['pid'];
        $item->name = $row['name'];
        $item->level = $row['level'];
        return $item;
    }

    /**
     * 获取指定父级下的地区列表
     * @param int $parentid 父级ID
     * @return RegionItem[]
     */
    private function gets(int $parentid): array
    {
        $result = $this->db->query("SELECT * FROM region WHERE pid = {$parentid} ORDER BY sort ASC");
        $items = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $item = new RegionItem();
            $item->id = $row['id'];
            $item->pid = $row['pid'];
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
    public static function update(int $adjust = 2)
    {
        libxml_use_internal_errors(true);
        $doc = new DomDocument();
        $doc->preserveWhiteSpace = false;
        $url = 'https://www.mca.gov.cn/mzsj/xzqh/2022/202201xzqh.html';
        $html = file_get_contents($url);
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $trs = $xpath->query('//tr');
        $rows = [];
        $cur_pid1 = 0;   // 省
        $cur_pid2 = 0;   // 市
        $cur_sort1 = 0;  // 省
        $cur_sort2 = 0;  // 市
        $cur_sort3 = 0;  // 区
        $name_id_map = [
            '西沙区' => '460321',
            '南沙区' => '460322',
        ];  // 修正数据
        foreach ($trs as $tr) {
            $tds = $xpath->query('./td', $tr);
            if ($tds->length != 9) {
                continue;
            }
            $id = trim($tds->item(1)->nodeValue);
            if ($id == '行政区划代码') {  // 表头
                continue;
            }
            $name = $tds->item(2)->nodeValue;
            $name = htmlentities($name);
            $name = str_replace('&nbsp;', ' ', $name);
            $name = trim($name);
            if (empty($name)) {
                continue;
            }
            // 识别省市区
            if (substr($id, -4) == '0000') {
                $tr_type = 1;  // 省、直辖市
            } elseif (substr($id, -2) == '00') {
                $tr_type = 2;  // 市
            } else {
                $tr_type = 3;  // 区
            }
            if ($tr_type == 1) {  // 省、直辖市、自治区、港澳台
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
                if ($adjust >= 2) {  // 港澳台调整
                    if (in_array($id, ['710000', '	810000', '820000'])) {
                        // 市
                        $cur_sort2 += 1;
                        $id2 = substr($id, 0, 2) . '0100';
                        $name2 = '市辖区';
                        $row = [
                            'id'    => $id2,
                            'pid'   => $cur_pid1,
                            'name'  => $name2,
                            'level' => 2,
                            'sort'  => $cur_sort2
                        ];
                        $rows[] = $row;
                        $cur_pid2 = $id2;
                        // 区
                        $cur_sort3 += 1;
                        $id3 = substr($id, 0, 2) . '0101';
                        $name3 = '市辖区';
                        $row = [
                            'id'    => $id3,
                            'pid'   => $cur_pid2,
                            'name'  => $name3,
                            'level' => 3,
                            'sort'  => $cur_sort3
                        ];
                        $rows[] = $row;
                    }
                }
            } elseif ($tr_type == 2) {  // 市、直辖县级
                // 类似【东莞市】【中山市】无3级数据的则加入一条【市辖区】
                if ($cur_pid2 != $id && $cur_sort2 > 0 && $cur_sort3 == 0) {
                    $id3 = substr($cur_pid2, 0, 4) . '01';
                    $name3 = '市辖区';
                    $row = [
                        'id'    => $id3,
                        'pid'   => $cur_pid2,
                        'name'  => $name3,
                        'level' => 3,
                        'sort'  => 1
                    ];
                    $rows[] = $row;
                }

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
            } else {  // 区
                if (!is_numeric($id)) {  // id不为数字的情况
                    if (isset($name_id_map[$name])) {
                        $id = $name_id_map[$name];
                    } else {
                        throw new RuntimeException("无法解析行政区划代码：{$name}");
                    }
                }
                $cur_pid2n = substr($id, 0, 4) . '00';
                if ($cur_pid2n != $cur_pid2) {
                    if ($adjust >= 1) {
                        $md = substr($id, 2, 2);
                        if ($md == '01') {  // 市辖区
                            $name2 = '市辖区';
                        } elseif ($md == '02') {  // 县
                            $name2 = '县';
                        } elseif ($md == '90') {  // 直辖县级
                            $name2 = '直辖县级';

                            // 类似【儋州市】无3级数据的则加入一条【市辖区】
                            if ($cur_sort3 == 0) {
                                $id3 = substr($cur_pid2, 0, 4) . '01';
                                $name3 = '市辖区';
                                $row = [
                                    'id'    => $id3,
                                    'pid'   => $cur_pid2,
                                    'name'  => $name3,
                                    'level' => 3,
                                    'sort'  => 1
                                ];
                                $rows[] = $row;
                            }
                        } else {
                            throw new RuntimeException("无法解析行政区划代码：{$id}");
                        }
                        $cur_sort2 += 1;
                        $row = [
                            'id'    => $cur_pid2n,
                            'pid'   => $cur_pid1,
                            'name'  => $name2,
                            'level' => 2,
                            'sort'  => $cur_sort2
                        ];
                        $rows[] = $row;
                        $cur_pid2 = $cur_pid2n;
                        $cur_sort3 = 0;
                    }
                }
                $cur_sort3 += 1;
                $row = [
                    'id'    => $id,
                    'pid'   => $cur_pid2,
                    'name'  => $name,
                    'level' => 3,
                    'sort'  => $cur_sort3
                ];
                $rows[] = $row;
            }
        }
        $db = new SQLite3(dirname(__DIR__, 2) . "/data/MCA.sqlite3", SQLITE3_OPEN_READWRITE);
        $db->exec('BEGIN TRANSACTION');
        $db->exec('DELETE FROM region');
        foreach ($rows as $row) {
            $sql = "INSERT INTO region (id, pid, name, level, sort) VALUES ({$row['id']}, {$row['pid']}, '{$row['name']}', '{$row['level']}', {$row['sort']})";
            $db->exec($sql);
        }
        $db->exec('COMMIT');
        $db->exec('VACUUM');  // 数据压缩
    }
}
