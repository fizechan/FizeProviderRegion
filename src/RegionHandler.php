<?php


namespace fize\provider\region;

/**
 * 接口：省市区
 */
abstract class RegionHandler
{

    /**
     * @var array 配置
     */
    protected $config;

    /**
     *  构造
     * @param array $config 配置
     */
    public function __construct(array $config = null)
    {
        $this->config = $config;
    }

    /**
     * 根据编码获取完整信息
     * 返回一个长度为3的数组，依次是省市区，为null表示没有指定该数据
     * @param int $id 编码
     * @return RegionItem[]
     */
    abstract public function get($id);

    /**
     * 获取省列表
     * @return RegionItem[]
     */
    abstract public function getProvince();

    /**
     * 根据省编码返回市列表
     * @param int $provinceId 省编码
     * @return RegionItem[]
     */
    abstract public function getCity($provinceId);

    /**
     * 根据市编码返回区列表
     * @param int $cityId 市编码
     * @return RegionItem[]
     */
    abstract public function getArea($cityId);
}
