<?php
namespace ThinkMU\Tool;

use ThinkMU\Db\Sqlite3\OrmSqlite3;

/**
 * Description of Region
 *
 * @author Fize Chan
 * @version V2.0.0.20170620
 */
class Local
{
	
	private $model;
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		$this->model = new OrmSqlite3(dirname(dirname(__FILE__)) . "/Data/Region.sqlite3");
		$this->model->table("region");
	}
	
	/**
	 * 析构函数
	 */
	public function __destruct(){
		$this->model = null;
	}
	
	/**
	 * 获取指定父级下的地区列表，返回顶级列表时$parentid=0
	 * @param int $parentid 父级ID，返回顶级列表时$parentid=0
	 * @param array $fields 指定返回的字段，有:id、areaname、parentid、shortname、lng、lat、level、position、sort可选。默认返回'id', 'areaname', 'shortname', 'level'
	 * @return array
	 */
	public function getList($parentid, $fields = null){
		if(is_null($fields)){
			$fields = ['id', 'areaname', 'shortname', 'level'];
		}
		return $this->model->where(['parentid' => $parentid])->field($fields)->order("`sort` ASC")->select();
	}
}