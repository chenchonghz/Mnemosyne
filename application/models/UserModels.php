<?php
/**
 * 用户表
 *
 * Created by PhpStorm.
 * User: lin
 * Date: 15-11-10
 * Time: 下午11:26
 */
class UserModels extends CI_Model{
    private static $tableName   = 'user';

    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->model('UserLogModels');
        $this->load->library('util/Uuid');
    }


    /**
     * 新增用户
     *
     * @param $userPW
     * @param $userMobile
     * @param $userEmail
     * @return bool
     */
    public function addUser($userPW, $userMobile, $userEmail){
        $this->db->trans_start();
        $userId = Uuid::genUUID(CoreConst::USER_UUID);
        $this->db->insert(self::$tableName, array(
            'user_id'           => $userId,
            'user_password'     => $userPW,
            'user_create_time'  => time(),
            'user_status'       => 1,
            'user_mobile'       => $userMobile,
            'user_email'        => $userEmail,
        ));

        //打log
        $logContent = array(
            'user_mobile'   => $userMobile,
            'user_email'    => $userEmail,
        );


        $this->db->trans_complete();
        if (!$this->db->trans_status()){
            $logContent['run_status'] = 0;
        } else {
            $logContent['run_status'] = 1;
        }
        $this->UserLogModels->addUserLog($userId, $logContent, self::$tableName, __METHOD__);
        return $logContent['run_status'];
    }

    /**
     * 检查用户是否已经存在
     *
     *
     * @param $userMobile
     * @param $userEmail
     */
    public function checkUserExists($userMobile, $userEmail){
        $this->db->where('user_mobile', $userMobile);
        $this->db->or_where('user_email', $userEmail);
        return $this->db->count_all_results(self::$tableName);
    }


    /**
     * 获取用户基本信息
     *
     *  注意脱敏！！！
     *
     *  注意仅获取需要的字段！！！
     *
     * @param $userId
     * @param $selectColumn
     * @return array
     */
    public function getUserBasicInfo($userId, $selectColumn = null){
        if (null !== $selectColumn && is_array($selectColumn)){
            $this->db->select($selectColumn);
        }

        $this->db->where('user_id', $userId);
        $query  = $this->db->get(self::$tableName);
        $result = $query->row_array();
        return $result;
    }

    /**
     * 获取用户基本信息
     *
     * @param $userIdList
     * @return array
     */
    public function getUserBasicInfoList($userIdList){
        if(empty($userIdList))
        {
            return array();
        }
        $this->db->select('user_id, user_name');
        $this->db->where_in('user_id', $userIdList);
        $query  = $this->db->get(self::$tableName);
        $result = $query->result_array();
        return $result;
    }

    /**
     * 根据用户uuid获取用户全部基本信息
     *
     *  注意脱敏！！！
     *
     *  注意仅获取需要的字段！！！
     *
     * @param $userIdList
     * @param $selectColumn
     * @return array
     */
    public function getUserFullInfoList($userIdList, $selectColumn = array()){
        if(empty($userIdList))
        {
            return array();
        }

        if (null !== $selectColumn && is_array($selectColumn)){
            $this->db->select($selectColumn);
        }

        $this->db->where_in('user_id', $userIdList);
        $query  = $this->db->get(self::$tableName);
        $result = $query->result_array();
        return $result;
    }



    /**
     * 通过登陆的手机号或邮箱获取用户信息
     *
     * @param $userLoginName
     * @return Array
     */
    public function getUserInfoByLoginName($userLoginName)
    {
        $this->db->where('user_mobile', $userLoginName);
        $this->db->or_where('user_email', $userLoginName);
        $result  = $this->db->get(self::$tableName)->row_array();
        return $result;
    }

    /**
     * 修改用户基础数据
     *
     * @param $userId
     * @param $userName
     * @param $userBirthday
     * @param $userSex
     * @param $userMobile
     * @param $userEmail
     * @param $userSign
     * @param $userStatus
     * @param $userNickname
     * @return bool
     */
    public function updateUser($userId, $userName = null, $userBirthday = null, $userSex = null,
                               $userMobile = null, $userEmail = null, $userSign = null, $userStatus = null,
                               $userNickname = null){

        $arrUpdateConds = array();

        if (null !== $userName){
            $arrUpdateConds['user_name']     = $userName;
        }

        if (null !== $userBirthday){
            $arrUpdateConds['user_birthday'] = $userBirthday;
        }

        if (null !== $userSex){
            $arrUpdateConds['user_sex']      = $userSex;
        }

        if (null !== $userMobile){
            $arrUpdateConds['user_mobile']   = $userMobile;
        }

        if (null !== $userEmail){
            $arrUpdateConds['user_email']    = $userEmail;
        }

        if (null !== $userSign){
            $arrUpdateConds['user_sign']     = $userSign;
        }

        if (null !== $userNickname){
            $arrUpdateConds['user_nickname'] = $userNickname;
        }

        if (null !== $userStatus){
            $arrUpdateConds['user_status']   = $userStatus;
        }

        if (empty($arrUpdateConds)){
            return 0;
        }

        $this->db->trans_start();

        $this->db->where('user_id', $userId);
        $this->db->update(self::$tableName, $arrUpdateConds);

        //打log
        $arrUpdateConds['affected_rows'] = $this->db->affected_rows();

        $this->db->trans_complete();
        if (!$this->db->trans_status()){
            $arrUpdateConds['run_status'] = 0;
        } else {
            $arrUpdateConds['run_status'] = 1;
        }
        $this->UserLogModels->addUserLog($userId, $arrUpdateConds, self::$tableName, __METHOD__);
        return $arrUpdateConds['run_status'];
    }


    /**
     * 修改用户密码
     *
     * $userId
     * $password
     * @return bool
     */
    public function updatePassword($userId, $password){
        $this->db->trans_start();

        $this->db->where('user_id', $userId);
        $arrUpdateConds['user_password'] = $password;
        $this->db->update(self::$tableName, $arrUpdateConds);

        //打log
        $arrUpdateConds['affected_rows'] = $this->db->affected_rows();

        $this->db->trans_complete();

        if (!$this->db->trans_status()){
            $arrUpdateConds['run_status'] = 0;
        } else {
            $arrUpdateConds['run_status'] = 1;
        }
        $this->UserLogModels->addUserLog($userId, $arrUpdateConds, self::$tableName, __METHOD__);
        return $arrUpdateConds['run_status'];

    }

    /**
     * 更新用户头像
     *
     * @param $userId
     * @param $avatarUrl
     * @return int
     */
    public function modifyAvatar($userId, $avatarUrl){
        $this->db->where('user_id', $userId);
        $this->db->update(self::$tableName, array(
            'user_avatar' => $avatarUrl,
        ));

        //打log
        $arrUpdateConds['affected_rows'] = $this->db->affected_rows();

        $arrUpdateConds['affected_rows'] ? $arrUpdateConds['run_status'] = 1 : $arrUpdateConds['run_status'] = 0;

        $this->UserLogModels->addUserLog($userId, $arrUpdateConds, self::$tableName, __METHOD__);
        return $arrUpdateConds['run_status'];
    }

}
