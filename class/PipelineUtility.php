<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


class PipelineUtility
{

    /**
     * @param $input
     * @return string
     */
    static public function execute($input){

        /**
         * Get the value
         */
        $firstQuoteChar = strpos($input,'"');
        $input = substr($input,$firstQuoteChar+1);
        $secondQuoteChar = strpos($input,'"');
        $value = substr($input,0,$secondQuoteChar);
        $input = substr($input,$secondQuoteChar+1);

        /**
         * Go to the first | and delete it from the input
         */
        $pipeChar = strpos($input,'|');
        $input = substr($input,$pipeChar+1);

        /**
         * Get the command and applies them
         */
        $commands = preg_split("/\|/",$input);
        foreach ($commands as $command){
            $command = trim($command, " )");
            $leftParenthesis = strpos($command, "(");
            $commandName = substr($command, 0, $leftParenthesis);
            $signature = substr($command, $leftParenthesis+1);
            $commandArgs = preg_split("/,/",$signature);
            $commandArgs = array_map(
                'trim',
                $commandArgs,
                array_fill(0,sizeof($commandArgs),"\"")
            );
            switch ($commandName){
                case "replace":
                    $value = self::replace($commandArgs,$value);
                    break;
                case "head":
                    $value = self::head($commandArgs,$value);
                    break;
                case "tail":
                    $value = self::tail($commandArgs,$value);
                    break;
                case "rconcat":
                    $value = self::concat($commandArgs,$value,"right");
                    break;
                case "lconcat":
                    $value = self::concat($commandArgs,$value,"left");
                    break;
                default:
                    LogUtility::msg("command ($commandName) is unknown",LogUtility::LVL_MSG_ERROR,"pipeline");
            }
        }
        return $value;
    }

    private static function replace(array $commandArgs, $value)
    {
        $search = $commandArgs[0];
        $replace = $commandArgs[1];
        return str_replace($search,$replace,$value);
    }

    private static function head(array $commandArgs, $value)
    {
        $length = $commandArgs[0];
        return substr($value,0,$length);
    }

    private static function concat(array $commandArgs, $value,$side)
    {
        $string = $commandArgs[0];
        switch ($side){
            case "left":
                return $string .$value ;
            case "right":
                return $value . $string;
            default:
                LogUtility::msg("The side value ($side) is unknown",LogUtility::LVL_MSG_ERROR,"pipeline");
        }


    }

    private static function tail(array $commandArgs, $value)
    {
        $length = $commandArgs[0];
        return substr($value,strlen($value)-$length);
    }

}
