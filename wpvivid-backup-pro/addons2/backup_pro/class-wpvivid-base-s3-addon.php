<?php

if (!defined('WPVIVID_PLUGIN_DIR'))
{
    die;
}

if(class_exists("WPvivid_Base_S3"))
{
    class WPvivid_Base_S3_Ex extends WPvivid_Base_S3
    {
        public function listObject($bucket, $path)
        {
            $ret['result']='success';
            $results = array();
            $bcheck = true;
            $bcontinue = false;
            $continue_token = '';
            $start_after = '';
            $rest = new WPvivid_S3Request('GET', $bucket, '', $this->endpoint, $this);
            while($bcheck){
                $rest->unsetParameter($bucket);
                $rest->setParameter('list-type', 2);
                if($bcontinue) {
                    $rest->setParameter('start-after', $start_after);
                }
                else{
                    $rest->setParameter('prefix', $path);
                }
                $response = $rest->getResponse();
                if ($response->error === false && $response->code !== 200)
                {
                    $ret['result']='failed';
                    $ret['error']=$response['message'].' '.$response->code;
                    return $ret;
                }

                if ($response->error !== false)
                {
                    $ret['result']='failed';
                    $ret['error']=sprintf("S3::getBucket(): [%s] %s", $response->error['code'], $response->error['message']);
                    return $ret;
                }

                if (isset($response->body, $response->body->Contents))
                {
                    foreach ($response->body->Contents as $c)
                    {
                        $results[] = array(
                            'name' => (string)$c->Key,
                            'size' => (int)$c->Size,
                            'mtime'=> (string)$c->LastModified,
                        );
                        $start_after = (string)$c->Key;
                    }
                }

                if(isset($response->body->NextContinuationToken)){
                    $bcontinue = true;
                    $continue_token = $response->body->NextContinuationToken;
                    $start_after = $start_after;
                    $bcheck = true;
                }
                else{
                    $bcontinue = false;
                    $continue_token = '';
                    $bcheck = false;
                }
                $ret['result']='success';
                $ret['data']=$results;
                if(!$bcheck){
                    break;
                }
            }
            return $ret;
        }
    }
}
