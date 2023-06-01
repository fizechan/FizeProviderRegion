<?php


namespace Fize\Provider\Region;

/**
 * 接口：省市区
 */
abstract class RegionHandler
{

    /**
     * 接口：本地数据
     */
    const LOCAL = 'Local';

    /**
     * 接口：中华人民共和国民政部
     */
    const MCA = 'MCA';

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
    abstract public function get(int $id): array;

    /**
     * 获取省列表
     * @return RegionItem[]
     */
    abstract public function getProvinces(): array;

    /**
     * 根据省编码返回市列表
     * @param int $provinceId 省编码
     * @return RegionItem[]
     */
    abstract public function getCitys(int $provinceId): array;

    /**
     * 根据市编码返回区列表
     * @param int $cityId 市编码
     * @return RegionItem[]
     */
    abstract public function getAreas(int $cityId): array;

    /**
     * 获取完整名称
     * @param int    $id 编码
     * @param string $separator 间隔符
     * @param int    $adjust 调整方式：0-不调整；1-去除【市辖区、县】；
     * @return string
     */
    abstract public function getFullName(int $id, string $separator = '', int $adjust = 0): string;
}
