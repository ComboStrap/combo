<?php

namespace ComboStrap;

use Doku_Handler;

class Parser
{


    public static function dokuWikiParse($markup): Doku_Handler
    {
        global $ID;
        $keep = $ID;
        global $ACT;
        $keepAct = $ACT;
        if ($ID === null && PluginUtility::isTest()) {
            $ID = ExecutionContext::DEFAULT_SLOT_ID_FOR_TEST;
        }
        try {
            $ACT = "show";
            $modes = p_get_parsermodes();
            $handler = new Doku_Handler();
            $parser = new \dokuwiki\Parsing\Parser($handler);

            //add modes to parser
            foreach ($modes as $mode) {
                $parser->addMode($mode['mode'], $mode['obj']);
            }
            $parser->parse($markup);
            return $handler;
        } finally {
            $ID = $keep;
            $ACT = $keepAct;
        }
    }
}
