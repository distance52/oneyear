<?php

namespace App\Http\Controllers\Api\Administrator;

use Guzzle\Service\Resource\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;
use App\Models\Squad;

class PlanController extends Controller
{
    private function _getPlanStruct($id){
        $key='plan-'.$id;
        $plan_info = Cache::store('file')->remember($key, 1, function () use($id) {
            $oObj = Plan::whereId($id)->first();
            $struct = $oObj->struct;
            $cell_ids=array();
            $module_ids=array();
            $node_ids=array();
            $cell_arr=array();
            $module_arr=array();
            $node_arr=array();
            $module_parent=array();
            $node_parent=array();
            foreach($struct as $val){
                array_push($cell_ids,$val['id']);
                array_push($cell_arr,$val['id']);
                if(!isset($val['lists'])){
                    continue;
                }
                foreach($val['lists'] as $v){
                    array_push($module_ids,$v['id']);
                    $module_arr[$val['id']][]=$v['id'];
                    $module_parent[$v['id']]['parentId']=$val['id'];
                    if(!isset($v['lists'])){
                        continue;
                    }
                    foreach($v['lists'] as $_v){
                        array_push($node_ids,$_v['id']);
                        $node_arr[$v['id']][]=$_v['id'];
                        $node_parent[$_v['id']]['parentId']=$v['id'];
                    }
                }
            }
            $cell_info=Cell::whereIn('id',$cell_ids)->get(['name','id'])->toArray();
            $module_info=Module::whereIn('id',$module_ids)->get(['name','id'])->toArray();
            $node_info=Node::whereIn('id',$node_ids)->get(['name','id'])->toArray();
            foreach($module_info as $key=>&$val){
                $val['parentId']=$module_parent[$val['id']]['parentId'];
            }
            foreach($node_info as $key=>&$val){
                $val['parentId']=$node_parent[$val['id']]['parentId'];
            }
            return array('cell'=>$cell_info,'module'=>$module_info,'node'=>$node_info);
        });
        return $plan_info;
    }

    /**
     * 根据班级获取单元列表
     */
    public function getCellListBySquad($squad_id)
    {
        $squadInfo = Squad::where('id', $squad_id)->first();
        $plan_info = $this->_getPlanStruct($squadInfo->plan_id);
        $arr = array(
            'plan_id' => $squadInfo->plan_id,
            'cell' => $plan_info['cell']
        );
        return response()->json($arr);
    }

    /**
     * 根据方案id获取单元列表
     * @param $plan_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCellListByPlan($plan_id)
    {
        $plan_info = $this->_getPlanStruct($plan_id);
        return response()->json($plan_info['cell']);
    }

    /*
     *  获取某单元下的模块列表
     */
    public function getModuleListByCell($plan_id,$cell_id){
        $plan_info=$this->_getPlanStruct($plan_id);
        $module_arr=array();
        foreach($plan_info['module'] as &$val){
            if($val['parentId']==$cell_id){
                $module_arr[]=$val;
            }
        }
        return response()->json($module_arr);
    }

    /**
     *  获取某模块下的环节列表
     */
    public function getNodeListByModule($plan_id,$module_id){
        $plan_info=$this->_getPlanStruct($plan_id);
        $module_arr=array();
        foreach($plan_info['node'] as &$val){
            if($val['parentId']==$module_id){
                $module_arr[]=$val;
            }
        }
        return response()->json($module_arr);
    }
}
