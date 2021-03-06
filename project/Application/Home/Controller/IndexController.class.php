<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index() {
        if ($_GET['err']) {
            $this->assign('error', $_GET['err']);
        }
        if (session('?passport')) {
            $this->redirect('/home/desktop');
        }
        else {
            $this->redirect('/login');
        }
        $this->display();
    }

    public function verify() {
        header('Content-type: application/json');
        $p = I("post.");
        $passportMdl = D("Passport");

        $verify_code = trim($p['verify_code']);
        if (!$this->verify_code($verify_code)) {
            echo json_encode(array('status' => 'fail', 'msg' => "验证码错误"));
            exit;
        }

        $res = $passportMdl->verify($p['loginname'], $p['password']);
        if ('success' == $res['status']) {
            //写session
            $passport = $res['data']['passport'];
            unset($passport['password'], $passport['salt']);
            session('passport', $passport);
            echo json_encode(array('status' => 'success', 'data' => array('redirect_url' => '/home/desktop')));
            exit;
        }
        else {
            echo json_encode(array('status' => 'fail', 'msg' => "用户名或密码错误"));
            exit;
        }
    }

    public function register() {
        if (isset($_GET['referrer']) && $_GET['referrer']) {
            cookie('referrer', trim($_GET['referrer']), 86400*30);
        }
        $passportMdl = D("Passport");
        $this->display();
    }

    public function reg() {
        header('Content-type: application/json');
        $p = I("post.");
        $necessaryArgs = array(
            'loginname' => '用户名',
            'password' => '密码',
            'confirm_password' => '确认密码',
            'verify_code' => '验证码',
        );
        foreach ($necessaryArgs as $k => $v) {
            if (!isset($p[$k]) || empty($p[$k])) {
                echo json_encode(array('status' => 'fail', 'msg' => $v . "不能为空"));
                exit;
            }
        }

        if ($p['password'] != $p['confirm_password']) {
            echo json_encode(array('status' => 'fail', 'msg' => "两次密码不一致"));
            exit;
        }

        $verify_code = trim($p['verify_code']);
        if (!$this->verify_code($verify_code)) {
            echo json_encode(array('status' => 'fail', 'msg' => "验证码错误"));
            exit;
        }

        $passportMdl = D('Passport');
        $filter = array('loginname' => $p['loginname']);
        $row = $passportMdl->getRow($filter);
        if ($row) {
            echo json_encode(array('status' => 'fail', 'msg' => "用户名已存在"));
            exit;
        }

       $passport = $passportMdl->createNew($p);
        if ($passport) {
            unset($passport['password']);
            $this->_setLoginSession($passport);
            echo json_encode(array('status' => 'success', 'data' => array('redirect_url' => '/')));
            exit;
        }
        else {
            echo json_encode(array('status' => 'fail', 'msg' => "注册失败"));
            exit;
        }
    }

    public function logout() {
        session('passport', null);
        $this->redirect('/');
    }

    private function _setLoginSession($passport) {
        session('passport', $passport);
    }

    //验证验证码是否正确
    public function verify_code($code){
         $verify = new \Think\Verify();
         #return $verify->check($code);
         return true;
    }
}
