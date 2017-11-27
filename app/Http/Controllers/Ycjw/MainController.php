<?php

namespace App\Http\Controllers\Ycjw;

use Illuminate\Support\Facades\Auth;
use App\Models\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function bind(Request $request) {
        if(!$user = Auth::user()) {
            return RJM(null, -1, '没有认证信息');
        }
        $password = $request->get('password');
        list($check, $errmsg) = $this->getCheck($user->uno, $password, setting('ycjw_port'), true);
        if($check == false) {
            return RJM(null, -1, $errmsg);
        }
        $user->setExt('passwords.yc_password', encrypt($password));

        return RJM($user, 1, '绑定原创账号成功');
    }

    public function bindZf(Request $request) {
        if(!$user = Auth::user()) {
            return RJM(null, -1, '没有认证信息');
        }
        $password = $request->get('password');
        $api = new Api();
        $check = $api->getUEASData('score', $user->uno, [
            'zf' => $password
        ], '2017/2018(1)', null, true);
        if($check == false) {
            $error = $api->getError();
            if ($error === '用户名或密码错误') {
                $error = '用户名或密码错误, 正方密码是你选课的密码';
            }
            return RJM(null, -1, $error);
        }
        $user->setExt('passwords.zf_password', encrypt($password));

        return RJM($user, 1, '绑定正方账号成功');
    }

    /**
     * 循环获取
     * @param $username
     * @param $password
     * @param null $port
     * @param bool $retry
     * @param int $timeout
     * @return array
     */
    public function getCheck($username, $password, $port = null, $retry = false, $timeout = 800) {
        $api = new Api;
        $check = $api->checkYcLogin($username, $password, $port, $timeout);
        $firstError = $api->getError();
        $api->resetError();
        if($firstError === '原创服务器错误') {
            addYcjwPortError($port);
        }
        if(!$check && !$retry) {
            resetCurrentYcjwPort();
            if($firstError == '原创服务器错误') {
                return [false, '原创教务系统炸了'];
            }
            return [false, $api->getError()];
        }
        $error = '';
        if(!$check && $retry) {
            for ($i = 83; $i <= 86; $i++) {
                $check = $api->checkYcLogin($username, $password, $i, $timeout);
                $error = $api->getError();
                if($check) {
                    break;
                } else {
                    $api->resetError();
                    if($error === '原创服务器错误') {
                        addYcjwPortError($i);
                    }
                }
            }
            resetCurrentYcjwPort();
            if(!$check) {
                return [false, $error];
            }
        }
        return [$check, $error];
    }
}
