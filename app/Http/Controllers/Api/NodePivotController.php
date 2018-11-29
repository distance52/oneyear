<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teaching\Node;

// 环节和资源、试卷的接口逻辑
class NodePivotController extends Controller
{
    //
    public function show($node_id) {
        //
        $exampaper = $info = null;
        $objNode = Node::whereId($node_id)->first();
        if($objNode) {
            $exampaper = $objNode->exampaper()->first(['id','name']);
            $info = $objNode->info()->first(['id','name']);
        }
        return response()->json(compact('exampaper','info'));
    }

    //
    public function store(Request $request, $node_id, $type) {

        $objNode = Node::whereId($node_id)->first();
        if($objNode) {
            if($request->has('info_id')) {
                $objNode->info_id = (int)$request->input('info_id');
            }
            if($request->has('exampaper_id')) {
                $objNode->exampaper_id = (int)$request->input('exampaper_id');
            }
            $objNode->save();
            return response()->json(null);
        }

        $msg = [
              "custom-msg"=> ["失败，请重试"],
        ];
        return response()->json($msg)->setStatusCode(422);

    }
}
