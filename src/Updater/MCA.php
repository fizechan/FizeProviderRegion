<?php

namespace Fize\Provider\Region\Updater;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use SQLite3;

class MCA
{

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
                $id = substr($id, 0, 2);
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
                    if (in_array($id, ['71', '	81', '82'])) {
                        // 市
                        $cur_sort2 += 1;
                        $id2 = $id . '01';
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
                        $id3 = $id2 . '01';
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
                $id = substr($id, 0, 4);
                // “直筒子市”无3级数据的则加入一条【市辖区】
                if ($cur_pid2 != $id && $cur_sort2 > 0 && $cur_sort3 == 0) {
                    $id3 = $cur_pid2 . '00';
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
                $cur_pid2n = substr($id, 0, 4);
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
                                $id3 = $cur_pid2 . '00';
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
        $db->close();
    }

}