<?php


namespace Fize\Provider\Region;

/**
 * 接口：行政区划
 */
abstract class RegionHandlerInterface
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
     * 返回一个长度为5的数组，依次是【省-市-区-街道-社区】，为null表示没有指定该数据
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
    abstract public function getCountys(int $cityId): array;

    /**
     * 根据区编码返回街道列表
     * @param int $countId 区编码
     * @return RegionItem[]
     */
    abstract public function getTowns(int $countId): array;

    /**
     * 根据街道编码返回社区列表
     * @param int $townId 街道编码
     * @return RegionItem[]
     */
    abstract public function getVillages(int $townId): array;

    /**
     * 获取完整名称
     * @param int    $id 编码
     * @param string $separator 间隔符
     * @param int    $adjust 调整方式：0-不调整；1-去除【市辖区、县、直辖县级】；2-在1的基础上再去除中间市
     * @return string
     */
    abstract public function getFullName(int $id, string $separator = '', int $adjust = 0): string;

    /**
     * 根据编码获取扩展信息
     * @param int $id 编码
     * @return array
     */
    abstract public function getExtend(int $id): array;
}
