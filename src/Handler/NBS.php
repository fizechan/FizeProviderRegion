<?php

namespace Fize\Provider\Region\Handler;

use DOMDocument;
use DOMXPath;
use Fize\Provider\Region\RegionHandler;
use Fize\Provider\Region\RegionItem;
use RuntimeException;
use SQLite3;

/**
 * 国家统计局
 *
 * 本数据支持到level5，即【省-市-区-街道-社区】
 */
class NBS extends RegionHandler
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
        $this->db = new SQLite3(dirname(__DIR__, 2) . "/data/NBS.sqlite3", SQLITE3_OPEN_READONLY);
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
     */
    public static function update()
    {
        libxml_use_internal_errors(true);
        $regions = [];
        $villages = [];
        $data1s = self::down1();
        foreach ($data1s as $index1 => $data1) {
            $regions[] = [
                'id'    => $data1['id'],
                'pid'   => 0,
                'name'  => $data1['name'],
                'level' => 1,
                'sort'  => $index1 + 1
            ];
            echo "当前处理：{$data1['name']}\r\n";
            ob_flush();
            flush();
            $data2s = self::down2($data1['id']);
            foreach ($data2s as $index2 => $data2) {
                echo "当前进度：{$data1['id']}/{$data2['id']}\r\n";
                ob_flush();
                flush();
                $regions[] = [
                    'id'    => $data2['id'],
                    'pid'   => $data1['id'],
                    'name'  => $data2['name'],
                    'level' => 2,
                    'sort'  => $index2 + 1
                ];
                if ($data2['has3']) {
                    $data3s = self::down3($data1['id'], $data2['id']);
                    foreach ($data3s as $index3 => $data3) {
                        echo "当前进度：{$data1['id']}/{$data2['id']}/{$data3['id']}\r\n";
                        ob_flush();
                        flush();
                        $regions[] = [
                            'id'    => $data3['id'],
                            'pid'   => $data2['id'],
                            'name'  => $data3['name'],
                            'level' => 3,
                            'sort'  => $index3 + 1
                        ];
                        if ($data3['has4']) {
                            $data4s = self::down4($data3['uri4']);
                            foreach ($data4s as $index4 => $data4) {
                                echo "当前进度：{$data1['id']}/{$data2['id']}/{$data3['id']}/{$data4['id']}\r\n";
                                ob_flush();
                                flush();
                                $regions[] = [
                                    'id'    => $data4['id'],
                                    'pid'   => $data3['id'],
                                    'name'  => $data4['name'],
                                    'level' => 4,
                                    'sort'  => $index4 + 1
                                ];
                                if ($data4['has5']) {
                                    $data5s = self::down5($data4['uri5']);
                                    foreach ($data5s as $index5 => $data5) {
                                        $regions[] = [
                                            'id'    => $data5['id'],
                                            'pid'   => $data4['id'],
                                            'name'  => $data5['name'],
                                            'level' => 5,
                                            'sort'  => $index5 + 1
                                        ];
                                        $villages[] = [
                                            'id'   => $data5['id'],
                                            'type' => $data5['type']
                                        ];
                                    }
                                }
                                usleep(30000);
                            }
                        }
                    }
                }
            }
        }
        $db = new SQLite3(dirname(__DIR__, 2) . "/data/NBS.sqlite3", SQLITE3_OPEN_READWRITE);
        $db->exec('BEGIN TRANSACTION');
        $db->exec('DELETE FROM region');
        foreach ($regions as $region) {
            $sql = "INSERT INTO region (id, pid, name, level, sort) VALUES ({$region['id']}, {$region['pid']}, '{$region['name']}', '{$region['level']}', {$region['sort']})";
            $db->exec($sql);
        }
        $db->exec('DELETE FROM village');
        foreach ($villages as $village) {
            $sql = "INSERT INTO village (id, type) VALUES ({$village['id']}, {$village['type']})";
            $db->exec($sql);
        }
        $db->exec('COMMIT');
        $db->exec('VACUUM');  // 数据压缩
    }

    private static function down1()
    {
        $datas = [];
        $url = 'http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/index.html';
        $html = file_get_contents($url);
        $doc = new DomDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $trs = $xpath->query("//tr[@class='provincetr']");
        foreach ($trs as $tr) {
            $tds = $xpath->query('./td', $tr);
            foreach ($tds as $td) {
                $a = $xpath->query('./a', $td)->item(0);
                $datas[] = [
                    'id'   => (int)(str_replace('.html', '', $a->attributes['href']->value)),
                    'name' => trim($a->textContent)
                ];
            }
        }
        return $datas;
    }

    private static function down2($lv1Id)
    {
        $datas = [];
        $url = "http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/{$lv1Id}.html";
        $html = file_get_contents($url);
        $doc = new DomDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $trs = $xpath->query("//tr[@class='citytr']");
        foreach ($trs as $tr) {
            $tds = $xpath->query('./td', $tr);
            $a1 = $xpath->query('./a', $tds->item(0))->item(0);
            $a2 = $xpath->query('./a', $tds->item(1))->item(0);
            $has3 = false;
            if (!is_null($a1) && !is_null($a1->attributes['href'])) {
                $has3 = true;
            }
            $datas[] = [
                'id'   => (int)(substr(trim($a1->textContent), 0, 4)),
                'name' => trim($a2->textContent),
                'has3' => $has3
            ];
        }
        return $datas;
    }

    private static function down3($lv1Id, $lv2Id)
    {
        $datas = [];
        $url = "http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/{$lv1Id}/{$lv2Id}.html";
        $html = file_get_contents($url);
        $doc = new DomDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $trs = $xpath->query("//tr[@class='countytr']");
        foreach ($trs as $tr) {
            $tds = $xpath->query('./td', $tr);
            $a1 = $xpath->query('./a', $tds->item(0))->item(0);
            $a2 = $xpath->query('./a', $tds->item(1))->item(0);
            $has4 = false;
            $uri4 = null;
            if (!is_null($a1) && !is_null($a1->attributes['href'])) {
                $has4 = true;
                $href = $a1->attributes['href']->value;
                $uri4 = "{$lv1Id}/{$href}";
            }
            $datas[] = [
                'id'   => (int)(substr(trim($a1->textContent), 0, 6)),
                'name' => trim($a2->textContent),
                'has4' => $has4,
                'uri4' => $uri4
            ];
        }
        return $datas;
    }

    private static function down4($lv3Uri)
    {
        $datas = [];
        $hparts = explode('/', $lv3Uri);
        array_pop($hparts);
        $hdir = implode('/', $hparts);
        $url = "http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/{$lv3Uri}";
        $html = file_get_contents($url);
        $doc = new DomDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $trs = $xpath->query("//tr[@class='towntr']");
        foreach ($trs as $tr) {
            $tds = $xpath->query('./td', $tr);
            $a1 = $xpath->query('./a', $tds->item(0))->item(0);
            $a2 = $xpath->query('./a', $tds->item(1))->item(0);
            $has5 = false;
            $uri5 = null;
            if (!is_null($a1) && !is_null($a1->attributes['href'])) {
                $has5 = true;
                $href = $a1->attributes['href']->value;
                $uri5 = "{$hdir}/{$href}";
            }
            $datas[] = [
                'id'   => (int)(substr(trim($a1->textContent), 0, 9)),
                'name' => trim($a2->textContent),
                'has5' => $has5,
                'uri5' => $uri5
            ];
        }
        return $datas;
    }

    private static function down5($lv4Uri)
    {
        $datas = [];
        $url = "http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/{$lv4Uri}";
        $html = file_get_contents($url);
        $doc = new DomDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $trs = $xpath->query("//tr[@class='villagetr']");
        foreach ($trs as $tr) {
            $tds = $xpath->query('./td', $tr);
            $id = (int)trim($tds->item(0)->textContent);
            $type = (int)trim($tds->item(1)->textContent);
            $name = trim($tds->item(2)->textContent);
            $datas[] = [
                'id'   => $id,
                'name' => $name,
                'type' => $type
            ];
        }
        return $datas;
    }
}
