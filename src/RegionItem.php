<?php


namespace Fize\Provider\Region;

/**
 * 行政区划项
 */
final class RegionItem
{

    /**
     * @var int 行政区划代码
     */
    public $id;

    /**
     * @var int 父级行政区划代码
     */
    public $pid;

    /**
     * @var string 名称
     */
    public $name;

    /**
     * @var int 级别
     */
    public $level;

    /**
     * @var string 简称
     */
    public $shortName;

    public function getId()
    {

    }

    public function getPid()
    {

    }

    /**
     * 获取完整名称
     * @param string $separator 间隔符
     * @param int    $adjust 调整方式：0-不调整；1-去除【市辖区、县、直辖县级】；2-在1的基础上再去除中间市
     * @return string
     */
    public function getFullName(string $separator = '', int $adjust = 0): string
    {
        return '';
    }

    /**
     * 根据编码获取扩展信息
     * @return array
     */
    public function getExtend(): array
    {
        return [];
    }
}
