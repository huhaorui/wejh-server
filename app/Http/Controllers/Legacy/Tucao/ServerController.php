<?php
namespace App\Http\Controllers\Legacy\Tucao;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\SendTemplateMessage;
use Mockery\Exception;

class ServerController extends Controller
{
    /**
     * 处理吐个槽的webhook消息
     *
     * @return string
     */
    public function serve(Request $request)
    {
        try {
            $event = json_decode(file_get_contents("php://input"), true);
            // logger()->error(file_get_contents("php://input"));
            $type = $event['type'];
            $payload = $event['payload'];
            switch ($type) {
                case "post.created":
                    // 业务代码
                    $this->sendMessage('有一个新的留言', $payload);
                    return RJM(null, 1);
                    break;
                case "post.updated":
                    // 业务代码
                    $this->sendMessage('有一个留言被更新了', $payload);
                    return RJM(null, 1);
                    break;
                // case "reply.created":
                //     // 业务代码
                //     $this->sendMessage('有一个新的回复', $payload);
                //     return RJM(null, 1);
                //     break;
                case "reply.updated":
                    // 业务代码
                    $this->sendMessage('有一个回复被更新了', $payload);
                    return RJM(null, 1);
                    break;
                default:
                    return response('no match', 200);
                    break;
            }
        } catch (Exception $e) {
            return response('no match', 200);
        }
    }

    public function sendMessage($title, $payload) {
        $user_list = ['oIRN_twXj9BH2s5tRWc3oeAdHnBk'];
        foreach ($user_list as $key => $value) {
            $userId = $value;
            $templateId = 'zuvMsJbwiabXHJF7_bt9a7lYZDZxzpPT8tYl4WsuJHU'; // 模板消息id
            $url = 'http://support.qq.com/products/19048';
            $data = array(
                "first"  => $title,
                "keyword1"   => array_get($payload, 'post.user.nickname'),
                "keyword2"  => date('Y-m-d'),
                "remark" => array_get($payload, 'post.content'),
            );
            $job = new SendTemplateMessage($userId, $templateId, $url, $data);
            dispatch($job);
        }
    }
}