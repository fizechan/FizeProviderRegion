<?php

namespace Handler;

use DOMDocument;
use DOMXPath;
use Exception;
use Fize\Provider\Region\Handler\NBS;
use PHPUnit\Framework\TestCase;
use SQLite3;

class TestNBS extends TestCase
{

    public function testGetProvinces()
    {
        $nbs = new NBS();
        $items = $nbs->getProvinces();
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetCitys()
    {
        $nbs = new NBS();
        $items = $nbs->getCitys(500000);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetCountys()
    {
        $nbs = new NBS();
        $items = $nbs->getCountys(500200);
        var_dump($items);
        self::assertIsArray($items);
    }

    public function testGetFullName()
    {
        $nbs = new NBS();
        $name = $nbs->getFullName(110109, '-');
        var_dump($name);
        self::assertEquals('北京市-市辖区-门头沟区', $name);

        $name = $nbs->getFullName(110109, '', 1);
        var_dump($name);
        self::assertEquals('北京市门头沟区', $name);

        $name = $nbs->getFullName(442001, '', 1);
        var_dump($name);
        self::assertEquals('广东省中山市', $name);
    }

    public function testUpdate()
    {
        self::update();
        self::assertTrue(true);
    }

    /**
     * 更新数据
     */
    public static function update()
    {
        $db = new SQLite3(dirname(__DIR__, 2) . "/data/NBS.sqlite3", SQLITE3_OPEN_READWRITE);
        $db->exec('BEGIN TRANSACTION');
        $db->exec('DELETE FROM region');
        $db->exec('DELETE FROM village');
        $db->exec('COMMIT');

        libxml_use_internal_errors(true);
        $regions = [];
        $villages = [];
        $data1s = self::data1();
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
            $data2s = self::data2($data1['id']);
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
                    $data3s = self::data3($data1['id'], $data2['id']);
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
                            $data4s = self::data4($data1['id'], $data2['id'], $data3['id'], $data3['uri4']);
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
                                    $data5s = self::data5($data1['id'], $data2['id'], $data3['id'], $data4['id'], $data4['uri5']);
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
                                    if (count($regions) >= 10000 || count($villages) >= 10000) {
                                        self::save($regions, $villages);
                                        $regions = [];
                                        $villages = [];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        self::save($regions, $villages);
        $db->exec('VACUUM');
    }

    private static function save($regions, $villages)
    {
        $db = new SQLite3(dirname(__DIR__, 2) . "/data/NBS.sqlite3", SQLITE3_OPEN_READWRITE);
        $db->exec('BEGIN TRANSACTION');
        foreach ($regions as $region) {
            $sql = "INSERT INTO region (id, pid, name, level, sort) VALUES ({$region['id']}, {$region['pid']}, '{$region['name']}', '{$region['level']}', {$region['sort']})";
            $db->exec($sql);
        }
        foreach ($villages as $village) {
            $sql = "INSERT INTO village (id, type) VALUES ({$village['id']}, {$village['type']})";
            $db->exec($sql);
        }
        $db->exec('COMMIT');
    }

    private static function data1()
    {
        $cache_file = dirname(__DIR__, 2) . "/cache/2023.json";
        if (is_file($cache_file)) {
            $datas = json_decode(file_get_contents($cache_file), true);
            return $datas;
        }
        $datas = [];
        $url = 'http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/index.html';
        $html = self::htmlGet($url);
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
        file_put_contents($cache_file, json_encode($datas, JSON_UNESCAPED_UNICODE));
        usleep(30000);
        return $datas;
    }

    private static function data2($lv1Id)
    {
        $cache_file = dirname(__DIR__, 2) . "/cache/2023_{$lv1Id}.json";
        if (is_file($cache_file)) {
            $datas = json_decode(file_get_contents($cache_file), true);
            return $datas;
        }
        // 直筒子市，需要做特殊处理。
        $ztzs_ids = [
            4419,  // 广东省-东莞市
            4420,  // 广东省-中山市
            4604,  // 海南省-儋州市
        ];
        $datas = [];
        $url = "http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/{$lv1Id}.html";
        $html = self::htmlGet($url);
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
            $id = (int)(substr(trim($a1->textContent), 0, 4));
            $datas[] = [
                'id'   => $id,
                'name' => trim($a2->textContent),
                'has3' => $has3,
                'ztzs' => in_array($id, $ztzs_ids)
            ];
        }
        file_put_contents($cache_file, json_encode($datas, JSON_UNESCAPED_UNICODE));
        usleep(30000);
        return $datas;
    }

    private static function data3($lv1Id, $lv2Id)
    {
        $cache_file = dirname(__DIR__, 2) . "/cache/2023_{$lv1Id}_{$lv2Id}.json";
        if (is_file($cache_file)) {
            $datas = json_decode(file_get_contents($cache_file), true);
            return $datas;
        }
        $datas = [];
        // 直筒子市，需要做特殊处理。
        $ztzs_ids = [
            4419,  // 广东省-东莞市
            4420,  // 广东省-中山市
            4604,  // 海南省-儋州市
        ];
        if (in_array($lv2Id, $ztzs_ids)) {
            $uri4 = "{$lv1Id}/{$lv2Id}.html";
            $datas[] = [
                'id'   => (int)"{$lv2Id}00",
                'name' => '市辖区',
                'has4' => true,
                'uri4' => $uri4
            ];
            return $datas;
        }
        $url = "http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/{$lv1Id}/{$lv2Id}.html";
        $html = self::htmlGet($url);
        $doc = new DomDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $trs = $xpath->query("//tr[@class='countytr']");
        foreach ($trs as $tr) {
            $tds = $xpath->query('./td', $tr);
            $td0 = $tds->item(0);
            $a1 = $xpath->query('./a', $td0)->item(0);
            $td1 = $tds->item(1);
            $has4 = false;
            $uri4 = null;
            if (!is_null($a1) && !is_null($a1->attributes['href'])) {
                $has4 = true;
                $href = $a1->attributes['href']->value;
                $uri4 = "{$lv1Id}/{$href}";
            }
            $id = (int)(substr(trim($td0->textContent), 0, 6));
            $datas[] = [
                'id'   => $id,
                'name' => trim($td1->textContent),
                'has4' => $has4,
                'uri4' => $uri4
            ];
        }
        file_put_contents($cache_file, json_encode($datas, JSON_UNESCAPED_UNICODE));
        usleep(30000);
        return $datas;
    }

    private static function data4($lv1Id, $lv2Id, $lv3Id, $lv3Uri)
    {
        $cache_file = dirname(__DIR__, 2) . "/cache/2023_{$lv1Id}_{$lv2Id}_{$lv3Id}.json";
        if (is_file($cache_file)) {
            $datas = json_decode(file_get_contents($cache_file), true);
            return $datas;
        }
        $datas = [];
        $hparts = explode('/', $lv3Uri);
        array_pop($hparts);
        $hdir = implode('/', $hparts);
        $url = "http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/{$lv3Uri}";
        $html = self::htmlGet($url);
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
        file_put_contents($cache_file, json_encode($datas, JSON_UNESCAPED_UNICODE));
        usleep(30000);
        return $datas;
    }

    private static function data5($lv1Id, $lv2Id, $lv3Id, $lv4Id, $lv4Uri)
    {
        $cache_file = dirname(__DIR__, 2) . "/cache/2023_{$lv1Id}_{$lv2Id}_{$lv3Id}_{$lv4Id}.json";
        if (is_file($cache_file)) {
            $datas = json_decode(file_get_contents($cache_file), true);
            return $datas;
        }
        $datas = [];
        $url = "http://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/{$lv4Uri}";
        $html = self::htmlGet($url);
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
        file_put_contents($cache_file, json_encode($datas, JSON_UNESCAPED_UNICODE));
        usleep(30000);
        return $datas;
    }

    private static function htmlGet($url)
    {
        $cnt = 0;
        while ($cnt < 3) {
            $cnt++;
            try {
                $html = file_get_contents($url);
                return $html;
            } catch (Exception $exception) {
                if ($cnt >= 3) {
                    throw $exception;
                }
            }
        }
    }
}
