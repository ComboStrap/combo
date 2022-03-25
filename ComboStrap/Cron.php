<?php


namespace ComboStrap;


use Cron\CronExpression;

class Cron
{


    /**
     * @throws ExceptionCompile
     */
    public static function getDate(string $cronExpression): \DateTime
    {
        try {
            $cron = CronExpression::factory($cronExpression);
            return $cron->getNextRunDate();
        } catch (\InvalidArgumentException $e) {
            throw new ExceptionCompile("The cache frequency expression ($cronExpression) is not a valid cron expression. <a href=\"https://crontab.guru/\">Validate it on this website</a>");
        }
    }
}
