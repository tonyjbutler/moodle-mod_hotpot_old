<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Render an attempt at a HotPot quiz
 * Output format: hp_6_jmatch_html_intro
 *
 * @package   mod-hotpot
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// get parent class
require_once($CFG->dirroot.'/mod/hotpot/attempt/hp/6/jmatch/html/renderer.php');

/**
 * mod_hotpot_attempt_hp_6_jmatch_html_intro_renderer
 *
 * @copyright 2010 Gordon Bateson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class mod_hotpot_attempt_hp_6_jmatch_html_intro_renderer extends mod_hotpot_attempt_hp_6_jmatch_html_renderer {

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    public static function sourcetypes()  {
        return array('hp_6_jmatch_html_intro');
    }

    /**
     * fix_headcontent
     */
    function fix_headcontent()  {
        $this->fix_headcontent_rottmeier('jintro');
    }

    /**
     * get_js_functionnames
     *
     * @return xxx
     */
    function get_js_functionnames()  {
        // start list of function names
        $names = parent::get_js_functionnames();
        $names .= ($names ? ',' : '').'CheckAnswer';
        return $names;
    }

    /**
     * fix_js_CheckAnswer
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     */
    function fix_js_CheckAnswer(&$str, $start, $length)  {
        $substr = substr($str, $start, $length);

        // add extra argument to this function, so it can be called from stop button
        if ($pos = strpos($substr, ')')) {
            $substr = substr_replace($substr, 'ForceQuizStatus', $pos, 0);
        }

        // intercept checks
        if ($pos = strpos($substr, '{')) {
            $insert = "\n"
                ."	HP.onclickCheck();\n"
            ;
            $substr = substr_replace($substr, $insert, $pos+1, 0);
        }

        // set quiz status
        if ($pos = strpos($substr, 'if (TotalCorrect == F.length) {')) {
            $insert = ''
                ."if (TotalCorrect == F.length) {\n"
                ."		var QuizStatus = 4; // completed\n"
                ."	} else if (ForceQuizStatus){\n"
                ."		var QuizStatus = ForceQuizStatus; // 3=abandoned\n"
                ."	} else if (TimeOver){\n"
                ."		var QuizStatus = 2; // timed out\n"
                ."	} else {\n"
                ."		var QuizStatus = 1; // in progress\n"
                ."	}\n"
                ."	"
            ;
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        // remove call to Finish() function
        $substr = preg_replace('/\s*'.'setTimeout\(.*?\);/s', '', $substr);

        // remove call to WriteToInstructions() function
        $search = '/\s*'.'WriteToInstructions\(.*?\);/s';
        $substr = preg_replace($search, '', $substr);

        // remove superfluous if-block that contained WriteToInstructions()
        $search = '/\s*if \(\(is\.ie\)\&\&\(\!is\.mac\)\)\{\s*\}/s';
        $substr = preg_replace($search, '', $substr);

        // send results to Moodle, if necessary
        if ($pos = strrpos($substr, '}')) {
            if ($this->hotpot->delay3==hotpot::TIME_AFTEROK) {
                $flag = 1; // set form values only
            } else {
                $flag = 0; // set form values and send form
            }
            $insert = "\n"
                ."	if (QuizStatus > 1) {\n"
                ."		TimeOver = true;\n"
                ."		Locked = true;\n"
                ."		Finished = true;\n"
                ."	}\n"
                ."	if (Finished || HP.sendallclicks){\n"
                ."		if (ForceQuizStatus || QuizStatus==1){\n"
                ."			// send results immediately\n"
                ."			HP.onunload(QuizStatus);\n"
                ."		} else {\n"
                ."			// send results after delay\n"
                ."			setTimeout('HP.onunload('+QuizStatus+',$flag)', SubmissionTimeout);\n"
                ."		}\n"
                ."	}\n"
            ;
            $substr = substr_replace($substr, $insert, $pos, 0);
        }

        $str = substr_replace($str, $substr, $start, $length);
    }

    /**
     * get_stop_function_name
     *
     * @return xxx
     */
    function get_stop_function_name()  {
        return 'CheckAnswer';
    }

    /**
     * get_stop_function_args
     *
     * @return xxx
     */
    function get_stop_function_args()  {
        return hotpot::STATUS_ABANDONED;
    }

    /* ================================================ **
    HP6:
        GetViewportHeight,PageDim,TrimString,StartUp,GetUserName,
        ShowMessage,HideFeedback,SendResults,Finish,WriteToInstructions,
        ShowSpecialReadingForQuestion,
    JMatch:
        CheckAnswers,beginDrag
    JMatch-intro:
        StartUpInfo(?),DisplayIntroPage(?),BuildIntroPage(?)
    ** ================================================ */
}
