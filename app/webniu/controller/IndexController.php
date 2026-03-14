<?php

namespace app\webniu\controller;

use support\Request;

class IndexController
{
    public function index(Request $request)
    {
        return 'Hello webniu!';
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webniu']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
