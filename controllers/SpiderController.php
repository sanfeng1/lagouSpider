<?php

namespace app\controllers;

use app\models\Careers;
use Yii;
use yii\web\Controller;

class SpiderController extends Controller
{
    //记录数
    public $num = 0;
    //职位名称
    public $positionName = ['php'];
    //数据
    public $data;

    public $userAgent = '* Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    public function actionIndex()
    {
        $positionCount = Careers::find()
            ->select('city as name,count(id) as value')
            ->groupBy(['positionName','city'])
            ->having('positionName="php"')
            ->asArray()
            ->all();
        $avgSalary = Careers::find()
            ->select('city as name,count(id) as value')
            ->groupBy(['positionName','city'])
            ->having('positionName="php"')
            ->orderBy('value DESC')
            ->limit(5)
            ->asArray()
            ->all();
        foreach($avgSalary as &$city){
             $salaryMin = 0;
             $salaryMax = 0;
             $salary  = Careers::find()
                 ->select('salary')
                 ->where(['positionName'=>'php','city'=>"{$city['name']}"])
                 ->asArray()
                 ->all();
             foreach($salary as $v){
                 $res = explode('-',$v['salary']);
                 $salaryMin += $res[0];
                 $salaryMax += $res[1];
             }
            $city['tooltip'] = round($salaryMin/$city['value'],1).'k-'.round($salaryMax/$city['value'],1).'k';
            $city['value'] = round($salaryMax/$city['value'],1);
            $avgName[] = $city['name'];
        }

//        var_dump(json_encode($avgSalary));EXIT;
        return $this->render('index',[
            'positionCount'=>json_encode($positionCount),
            'avgName' => json_encode($avgName),
            'avgSalary' => json_encode($avgSalary),
            'positionName'=>'PHP'
        ]);
    }

    public function actionSpider()
    {
        $url="http://www.lagou.com/jobs/positionAjax.json?needAddtionalResult=false";
        foreach($this->positionName as $kd){
            $html = $this->actionCurlGet($url,'post',$kd);

            $career = json_decode($html,true);
            foreach($career['content']['positionResult']['result'] as $val) {
                $this->data[] = ["{$kd}","{$val['city']}","{$val['salary']}","{$val['education']}","{$val['workYear']}"];
            }
            if($this->num < 100){
                return $this->actionSpider();
            }
            Yii::$app->db->createCommand()->batchInsert('careers_table',
                ['positionName','city','salary','education','workYear'], $this->data)->execute();

        }
        return $this->actionIndex();
    }


    public function actionCurlGet($url,$method,$kd)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);            //设置访问的url地址
//      curl_setopt($ch,CURLOPT_HEADER,1);            //是否显示头部信息
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);           //设置超时
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,1000);
        curl_setopt($ch, CURLOPT_REFERER,'http://www.google.com');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:111.222.333.4', 'CLIENT-IP:111.222.333.4'));
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);   //用户访问代理 User-Agent
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);      //跟踪301
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果
        if($method=='post'){
            $this->num++;
            if($this->num==1){
                $post_data = [
                    'first' => true,
                    'pn' => $this->num,
                    'kd' => $kd
                ];
            }else{
                $post_data = [
                    'first' => false,
                    'pn' => $this->num,
                    'kd' => $kd
                ];
            }
            curl_setopt($ch, CURLOPT_POST, 1);
//            // 把post的变量加上
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    //clear
    public function actionClear(){
        $connection = \Yii::$app->db;
        $connection->createCommand("TRUNCATE TABLE careers_table")->execute();
        return $this->actionSpider();
    }

}
