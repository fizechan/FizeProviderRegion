<?php

namespace Fize\Provider\Region\Handler;

use Fize\Provider\Region\RegionHandlerInterface;
use Fize\Provider\Region\RegionItem;
use SQLite3;

/**
 * 国家统计局
 *
 * 本数据支持到level5，即【省-市-区-街道-社区】
 */
class NBS extends RegionHandlerInterface
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
     * 返回一个长度为5的数组，依次是【省-市-区-街道-社区】，为null表示没有指定该数据
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
     * 根据区编码返回街道列表
     * @param int $countyId 区编码
     * @return RegionItem[]
     */
    public function getTowns(int $countyId): array
    {
        return [];
    }

    /**
     * 根据街道编码返回社区列表
     * @param int $townId 街道编码
     * @return RegionItem[]
     */
    public function getVillages(int $townId): array
    {
        return [];
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
}
