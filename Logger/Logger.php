<?php

namespace LogBundle\Logger;


class Logger
{
    private $base_path;

    public function __construct($location)
    {
         $this->base_path = $location;
    }

    public function logTransaction($request, $userId, $description)
    {
        $weekStartDate = new \DateTime('today');
        $weekStartDate->modify('this week -1 day');

        $weekEndDate = new \DateTime('today');
        $weekEndDate->modify('this week +5 days');

        $todayDate = new \DateTime('today');

        $fileName = $todayDate->format('W').'-'.$weekStartDate->format('Md').'-'.$weekEndDate->format('Md').'.log';
        $fileName = strtoupper($fileName);

        $path = $this->base_path.'/'.date('Y');
        if (!is_dir($path)) {
            $ret = mkdir($path, 0755, true);
            if (!$ret) {
                throw new \RuntimeException('Could not create target directory for log files.');
            }
        }

        if(is_array($description)){
            $transaction = '';
            foreach($description as $message){
                $transaction .= '[' . date('Y-d-m H:i:s') . ']:[' . date('T') . ']:[' . $request->getClientIp() . ']:[' . $userId . ']:[' . $request->getUri() . ']:[' . $message . ']';
                $transaction .= "\n";
            }
        }else {
            $transaction = '[' . date('Y-d-m H:i:s') . ']:[' . date('T') . ']:[' . $request->getClientIp() . ']:[' . $userId . ']:[' . $request->getUri() . ']:[' . $description . ']';
            $transaction .= "\n";
        }

        if(strlen($transaction) > 0){
            if (!file_put_contents($path.'/'.$fileName,$transaction, FILE_APPEND))
            {
                throw new \RuntimeException('Could not write to log file.');
            }
        }

    }
}
