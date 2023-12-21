<?php

namespace Fize\Provider\Region\Handler;

use Fize\Provider\Region\RegionHandlerInterface;
use Fize\Provider\Region\RegionItem;
use SQLite3;

/**
 * 中华人民共和国民政部
 *
 * 本数据仅支持到level3，即【省-市-区】
 */
class MCA extends RegionHandlerInterface
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
     * 返回一个长度为5的数组，依次是【省-市-区-街道-社区】，为null表示没有指定该数据
     * @param int $id 编码
     * @return RegionItem[]|null[]
     */
    public function get(int $id): array
    {
        $item1 = $this->getItem($id);
        if (is_null($item1)) {
            return [null, null, null, null, null];
        }
        if (empty($item1->pid)) {
            return [$item1, null, null, null, null];
        }
        $item2 = $this->getItem($item1->pid);
        if (empty($item2->pid)) {
            return [$item1, $item2, null, null, null];
        }
        $item3 = $this->getItem($item2->pid);
        return [$item1, $item2, $item3, null, null];
    }

    /**
     * 获取省列表
     * @return RegionItem[]
     */
    public function getProvinces(): array
    {
        return $this->getItems(0);
    }

    /**
     * 根据省编码返回市列表
     * @param int $provinceId 省编码
     * @return RegionItem[]
     */
    public function getCitys(int $provinceId): array
    {
        return $this->getItems($provinceId);
    }

    /**
     * 根据市编码返回区列表
     * @param int $cityId 市编码
     * @return RegionItem[]
     */
    public function getCountys(int $cityId): array
    {
        return $this->getItems($cityId);
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
     * @param int    $id 编码
     * @param string $separator 间隔符
     * @param int    $adjust 调整方式：0-不调整；1-去除【市辖区、县、直辖县级】；2-在1的基础上再去除中间市
     * @return string
     */
    public function getFullName(int $id, string $separator = '', int $adjust = 0): string
    {
        $row1 = $this->db->querySingle("SELECT * FROM region WHERE id = {$id}", true);
        if (empty($row1)) {
            return '';
        }
        $full_name = '';
        $bad_strs = ['市辖区', '县', '直辖县级'];
        if (!in_array($row1['name'], $bad_strs) || $adjust == 0) {
            $full_name = $row1['name'];
        }
        $row2 = $this->db->querySingle("SELECT * FROM region WHERE id = {$row1['pid']}", true);
        if (!in_array($row2['name'], $bad_strs) || $adjust == 0) {
            $full_name = $row2['name'] . $separator . $full_name;
        }
        $row3 = $this->db->querySingle("SELECT * FROM region WHERE id = {$row2['pid']}", true);
        if (!in_array($row3['name'], $bad_strs) || $adjust == 0) {
            $full_name = $row3['name'] . $separator . $full_name;
        }
        return $full_name;
    }

    /**
     * 根据ID返回项
     * @param int $id ID
     * @return RegionItem 未找到返回null
     */
    private function getItem(int $id)
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
     * @param int $pid 父级ID
     * @return RegionItem[]
     */
    private function getItems(int $pid): array
    {
        $result = $this->db->query("SELECT * FROM region WHERE pid = {$pid} ORDER BY sort ASC");
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
