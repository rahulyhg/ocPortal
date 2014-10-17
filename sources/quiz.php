<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    quizzes
 */

/**
 * Show a quiz box.
 *
 * @param  array                        The database row
 * @param  string                       The zone to show in
 * @param  boolean                      Whether to include context (i.e. say WHAT this is, not just show the actual content)
 * @param  ID_TEXT                      Overridden GUID to send to templates (blank: none)
 * @return tempcode                     The rendered quiz link
 */
function render_quiz_box($row, $zone = '_SEARCH', $give_context = true, $guid = '')
{
    require_lang('quiz');

    $date = get_timezoned_date($row['q_add_date']);
    $url = build_url(array('page' => 'quiz', 'type' => 'do', 'id' => $row['id']), $zone);

    $just_quiz_row = db_map_restrict($row, array('id', 'q_start_text'));

    $name = get_translated_text($row['q_name']);
    $start_text = get_translated_tempcode('quizzes', $just_quiz_row, 'q_start_text');

    if (has_privilege(get_member(), 'bypass_quiz_timer')) {
        $row['q_timeout'] = null;
    }

    $timeout = is_null($row['q_timeout']) ? '' : display_time_period($row['q_timeout'] * 60);
    $redo_time = ((is_null($row['q_redo_time'])) || ($row['q_redo_time'] == 0)) ? '' : display_time_period($row['q_redo_time'] * 60 * 60);

    return do_template('QUIZ_BOX', array(
        '_GUID' => ($guid != '') ? $guid : '3ba4e19d93eb41f6cf2d472af982116e',
        'GIVE_CONTEXT' => $give_context,
        '_TYPE' => $row['q_type'],
        'POINTS' => strval($row['q_points_for_passing']),
        'TIMEOUT' => $timeout,
        'REDO_TIME' => $redo_time,
        'TYPE' => do_lang_tempcode($row['q_type']),
        'DATE' => $date,
        'URL' => $url,
        'NAME' => $name,
        'START_TEXT' => $start_text,
        'ID' => strval($row['id']),
    ));
}

/**
 * Get quiz data for exporting it as a CSV.
 *
 * @param   AUTO_LINK   Quiz ID
 * @return  array       Quiz data array
 */
function get_quiz_data_for_csv($quiz_id)
{
    $questions_rows = $GLOBALS['SITE_DB']->query_select('quiz_questions', array('*'), array('q_quiz' => $quiz_id), 'ORDER BY q_order');

    $csv_data = array();

    // Create header array
    $header = array(do_lang('MEMBER'), do_lang('EMAIL'));

    // Get all entries and member answers of this quiz in to an array
    $member_answer_rows = $GLOBALS['SITE_DB']->query_select('quiz_entry_answer t1 JOIN ' . get_table_prefix() . 'quiz_entries t2 ON t2.id=t1.q_entry JOIN ' . get_table_prefix() . 'quiz_questions t3 ON t3.id=t1.q_question', array('t2.id AS entry_id', 'q_question', 'q_member', 'q_answer', 'q_results'), array('t2.q_quiz' => $quiz_id), 'ORDER BY q_order');
    $member_answers = array();
    foreach ($member_answer_rows as $id => $answer_entry) {
        $member_entry_key = strval($answer_entry['q_member']) . '_' . strval($answer_entry['entry_id']) . '_' . strval($answer_entry['q_results']);
        $question_id = $answer_entry['q_question'];
        if (!isset($member_answers[$member_entry_key][$question_id])) {
            $member_answers[$member_entry_key][$question_id] = array();
        }
        $member_answers[$member_entry_key][$question_id] = $answer_entry['q_answer'];
    }

    // Proper answers, for non-free-form questions
    $answer_rows = $GLOBALS['SITE_DB']->query_select('quiz_question_answers a JOIN ' . get_table_prefix() . 'quiz_questions q ON q.id=a.q_question', array('q_answer_text', 'q_question', 'a.id'), array('q_quiz' => $quiz_id), 'ORDER BY id');

    // Loop over it all
    foreach ($member_answers as $member_bits => $member_answers) {
        list($member, , $result) = explode('_', $member_bits, 3);
        $username = $GLOBALS['FORUM_DRIVER']->get_username(intval($member));
        $member_email = $GLOBALS['FORUM_DRIVER']->get_member_email_address(intval($member));

        $member_answers_csv = array();
        $member_answers_csv[do_lang('IDENTIFIER')] = $member;
        $member_answers_csv[do_lang('USERNAME')] = $username;
        $member_answers_csv[do_lang('EMAIL')] = $member_email;
        $member_answers_csv[do_lang('MARKS')] = $result;
        foreach ($questions_rows as $i => $question_row) {
            $member_answer = array_key_exists($question_row['id'], $member_answers) ? $member_answers[$question_row['id']] : '';

            if (is_numeric($member_answer)) {
                foreach ($answer_rows as $question_answer_row) {
                    if (($question_answer_row['id'] == intval($member_answer)) && ($question_answer_row['q_question'] == $question_row['id'])) {
                        $member_answer = get_translated_text($question_answer_row['q_answer_text']);
                    }
                }
            }

            $member_answers_csv[integer_format($i + 1) . ') ' . get_translated_text($question_row['q_question_text'])] = $member_answer;
        }

        $csv_data[] = $member_answers_csv;
    }

    return $csv_data;
}

/**
 * Get quiz data for exporting it as CSV.
 *
 * @param   array       The quiz questions
 * @return  tempcode    The rendered quiz
 */
function render_quiz($questions)
{
    require_lang('quiz');

    require_code('form_templates');

    // Sort out qa input
    $fields = new ocp_tempcode();
    foreach ($questions as $i => $q) {
        $name = 'q_' . strval($q['id']);
        $question = protect_from_escaping((!is_string($q['q_question_text']) && !isset($q['q_question_text__text_parsed'])) ? comcode_to_tempcode($q['q_question_text']) : get_translated_tempcode('quiz_questions', $q, 'q_question_text'));
        $description = protect_from_escaping((!is_string($q['q_question_extra_text']) && !isset($q['q_question_extra_text__text_parsed'])) ? comcode_to_tempcode($q['q_question_extra_text']) : get_translated_tempcode('quiz_questions', $q, 'q_question_extra_text'));

        switch ($q['q_type']) {
            case 'MULTIPLECHOICE':
                $radios = new ocp_tempcode();
                foreach ($q['answers'] as $a) {
                    $answer_text = (!is_string($a['q_answer_text']) && !isset($a['q_answer_text__text_parsed'])) ? comcode_to_tempcode($a['q_answer_text']) : get_translated_tempcode('quiz_question_answers', $a, 'q_answer_text');
                    $radios->attach(form_input_radio_entry($name, strval($a['id']), false, protect_from_escaping($answer_text)));
                }
                $fields->attach(form_input_radio($question, $description, $name, $radios, $q['q_required'] == 1));
                break;

            case 'MULTIMULTIPLE':
                $content = array();
                foreach ($q['answers'] as $a) {
                    $content[] = array(protect_from_escaping((!is_string($a['q_answer_text']) && !isset($a['q_answer_text__text_parsed'])) ? comcode_to_tempcode($a['q_answer_text']) : get_translated_tempcode('quiz_question_answers', $a, 'q_answer_text')), $name . '_' . strval($a['id']), false, '');
                }
                $fields->attach(form_input_various_ticks($content, $description, null, $question, true));
                break;

            case 'LONG':
                $fields->attach(form_input_text($question, $description, $name, '', $q['q_required'] == 1));
                break;

            case 'SHORT':
            case 'SHORT_STRICT':
                $fields->attach(form_input_line($question, $description, $name, '', $q['q_required'] == 1));
                break;

            default:
                warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
    }

    return $fields;
}

/**
 * Score a particular quiz entry.
 *
 * @param   AUTO_LINK   Entry ID
 * @param   ?AUTO_LINK  Quiz ID (NULL: look up from entry ID)
 * @param   ?array      Quiz row (NULL: look up from entry ID)
 * @param   ?array      Question rows (NULL: look up from entry ID)
 * @param   boolean     Whether to show answers, regardless of whether the quiz is set to do so
 * @return  array       A tuple of quiz result details
 */
function score_quiz($entry_id, $quiz_id = null, $quiz = null, $questions = null, $reveal_all = false)
{
    if (is_null($quiz_id)) {
        $quiz_id = $GLOBALS['SITE_DB']->query_select_value('quiz_entries', 'q_quiz', array('id' => $entry_id));
    }
    if (is_null($quiz_id)) {
        $quizzes = $GLOBALS['SITE_DB']->query_select('quizzes', array('*'), array('id' => $quiz_id), '', 1);
        if (!array_key_exists(0, $quizzes)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $quiz = $quizzes[0];
    }

    $__given_answers = $GLOBALS['SITE_DB']->query_select('quiz_entry_answer', array('q_question', 'q_answer'), array('q_entry' => $entry_id));
    $_given_answers = array();
    foreach ($__given_answers as $_given_answer) {
        if (!isset($_given_answers[$_given_answer['q_question']])) {
            $_given_answers[$_given_answer['q_question']] = array();
        }
        $_given_answers[$_given_answer['q_question']][] = $_given_answer['q_answer'];
    }

    if (is_null($questions)) {
        $questions = $GLOBALS['SITE_DB']->query_select('quiz_questions', array('*'), array('q_quiz' => $quiz_id), 'ORDER BY q_order');
        foreach ($questions as $i => $question) {
            $answers = $GLOBALS['SITE_DB']->query_select('quiz_question_answers', array('*'), array('q_question' => $question['id']), 'ORDER BY id');
            $questions[$i]['answers'] = $answers;
        }
    }

    $marks = 0.0;
    $potential_extra_marks = 0;
    $out_of = 0;
    $given_answers = array();
    $corrections = array();
    $affirmations = array();
    $unknowns = array();
    foreach ($questions as $i => $question) {
        if (!array_key_exists($question['id'], $_given_answers)) {
            continue;
        } // Question did not exist when this quiz entry was filled
        if ($question['q_marked'] == 0) {
            continue;
        } // Don't count non-marked questions

        $question_text = get_translated_text($question['q_question_text']);

        if ($question['q_type'] == 'SHORT' || $question['q_type'] == 'SHORT_STRICT' || $question['q_type'] == 'LONG') { // Text box ("free question"). May be an actual answer, or may not be
            $given_answer = $_given_answers[$question['id']][0];

            $correct_answer = new ocp_tempcode();
            $correct_explanation = mixed();
            if (count($question['answers']) == 0) {
                $potential_extra_marks++;
                $unknowns[] = array($question_text, $given_answer);
                $was_correct = mixed();
            } else {
                $was_correct = false;
                foreach ($question['answers'] as $a) {
                    if ($a['q_is_correct'] == 1) {
                        $correct_answer = make_string_tempcode(get_translated_text($a['q_answer_text']));
                    }
                    if (get_translated_text($a['q_answer_text']) == $given_answer) {
                        $correct_explanation = get_translated_text($a['q_explanation']);
                    }
                }
                $was_correct = typed_answer_is_correct($given_answer, $question['answers'], $question['q_type'] == 'SHORT_STRICT');
                if ($was_correct) {
                    $marks++;

                    $affirmation = array($question['id'], $question_text, $correct_answer, $given_answer);
                    if ((!is_null($correct_explanation)) && ($correct_explanation != '')) {
                        $affirmation[] = $correct_explanation;
                    }
                    $affirmations[] = $affirmation;
                } else {
                    $correction = array($question['id'], $question_text, $correct_answer, $given_answer);
                    if ((!is_null($correct_explanation)) && ($correct_explanation != '')) {
                        $correction[] = $correct_explanation;
                    }
                    $corrections[] = $correction;
                }
            }

            $given_answers[] = array(
                'QUESTION' => $question_text,
                'GIVEN_ANSWER' => $given_answer,
                'WAS_CORRECT' => $was_correct,
                'CORRECT_ANSWER' => $correct_answer,
                'CORRECT_EXPLANATION' => $correct_explanation,
            );
        } elseif ($question['q_type'] == 'MULTIMULTIPLE') { // Check boxes
            // Vector distance
            $wrongness = 0.0;
            $accum = new ocp_tempcode();
            $correct_answer = new ocp_tempcode();
            $correct_explanation = null;
            foreach ($question['answers'] as $a) {
                $for_this = in_array(strval($a['id']), $_given_answers[$question['id']]);
                $should_be_this = ($a['q_is_correct'] == 1);

                if ($for_this != $should_be_this) {
                    $wrongness++;
                }

                if ($should_be_this) {
                    if (!$correct_answer->is_empty()) {
                        $correct_answer->attach(do_lang_tempcode('LIST_SEP'));
                    }
                    $correct_answer->attach(get_translated_text($a['q_answer_text']));
                    $correct_explanation = get_translated_text($a['q_explanation']);
                }

                if ($for_this) {
                    if (!$accum->is_empty()) {
                        $accum->attach(do_lang_tempcode('LIST_SEP'));
                    }
                    $accum->attach(get_translated_text($a['q_answer_text']));
                }
            }
            // Normalise it
            $wrongness /= count($question['answers']);
            // And get our complement
            $correctness = 1.0 - $wrongness;
            $marks += $correctness;

            $marks += $correctness;

            if ($correctness != 1.0) {
                $correction = array($question['id'], $question_text, $correct_answer, $accum);
                if ((!is_null($correct_explanation)) && ($correct_explanation != '')) {
                    $correction[] = $correct_explanation;
                }
                $corrections[] = $correction;
            }

            $given_answer = $accum->evaluate();

            $given_answers[] = array(
                'QUESTION' => $question_text,
                'GIVEN_ANSWER' => $given_answer,
                'WAS_CORRECT' => $correctness == 1.0,
                'CORRECT_ANSWER' => $correct_answer,
                'CORRECT_EXPLANATION' => $correct_explanation,
            );
        } elseif ($question['q_type'] == 'MULTIPLECHOICE') { // Radio buttons
            $was_correct = false;
            $correct_answer = new ocp_tempcode();
            $correct_explanation = null;
            $given_answer = '';
            foreach ($question['answers'] as $a) {
                if ($a['q_is_correct'] == 1) {
                    $correct_answer = make_string_tempcode(get_translated_text($a['q_answer_text']));
                }

                if ($_given_answers[$question['id']][0] == strval($a['id'])) {
                    $given_answer = get_translated_text($a['q_answer_text']);

                    if ($a['q_is_correct'] == 1) {
                        $was_correct = true;

                        $marks++;
                    }

                    $correct_explanation = get_translated_text($a['q_explanation']);
                }
            }

            if (!$was_correct) {
                $correction = array($question['id'], $question_text, $correct_answer, $given_answer);
                if ((!is_null($correct_explanation)) && ($correct_explanation != '')) {
                    $correction[] = $correct_explanation;
                }
                $corrections[] = $correction;
            } else {
                $affirmation = array($question['id'], $question_text, $correct_answer, $given_answer);
                if ((!is_null($correct_explanation)) && ($correct_explanation != '')) {
                    $affirmation[] = $correct_explanation;
                }
                $affirmations[] = $affirmation;
            }

            $given_answers[] = array(
                'QUESTION' => $question_text,
                'GIVEN_ANSWER' => $given_answer,
                'WAS_CORRECT' => $was_correct,
                'CORRECT_ANSWER' => $correct_answer,
                'CORRECT_EXPLANATION' => $correct_explanation,
            );
        }

        $out_of++;
    }
    if ($out_of == 0) {
        $out_of = 1;
    }
    $minimum_percentage = intval(round(100.0 * $marks / $out_of));
    $maximum_percentage = intval(round(100.0 * ($marks + $potential_extra_marks) / $out_of));
    $marks_range = float_format($marks, 2, true) . (($potential_extra_marks == 0) ? '' : ('-' . float_format($marks + $potential_extra_marks, 2, true)));
    $percentage_range = strval($minimum_percentage) . (($potential_extra_marks == 0) ? '' : ('-' . strval($maximum_percentage)));

    // Prepare results for display
    $corrections_to_staff = new ocp_tempcode();
    $corrections_to_member = new ocp_tempcode();
    $affirmations_to_member = new ocp_tempcode();
    foreach ($corrections as $correction) {
        if ((array_key_exists(4, $correction)) || ($quiz['q_reveal_answers'] == 1) || ($reveal_all)) {
            $__correction = do_lang_tempcode(
                array_key_exists(4, $correction) ? 'QUIZ_MISTAKE_EXPLAINED_HTML' : 'QUIZ_MISTAKE_HTML',
                escape_html(is_object($correction[1]) ? $correction[1]->evaluate() : $correction[1]),
                escape_html(is_object($correction[3]) ? $correction[3]->evaluate() : $correction[3]),
                array(
                    escape_html(is_object($correction[2]) ? $correction[2]->evaluate() : $correction[2]),
                    escape_html(array_key_exists(4, $correction) ? $correction[4] : ''),
                )
            );
            $corrections_to_member->attach($__correction);
        }
        $_correction = do_lang(
            array_key_exists(4, $correction) ? 'QUIZ_MISTAKE_EXPLAINED_COMCODE' : 'QUIZ_MISTAKE_COMCODE',
            comcode_escape(is_object($correction[1]) ? $correction[1]->evaluate() : $correction[1]),
            comcode_escape(is_object($correction[3]) ? $correction[3]->evaluate() : $correction[3]),
            array(
                comcode_escape(is_object($correction[2]) ? $correction[2]->evaluate() : $correction[2]),
                comcode_escape(array_key_exists(4, $correction) ? $correction[4] : ''),
            )
        );
        $corrections_to_staff->attach($_correction);
    }
    foreach ($affirmations as $affirmation) {
        if (array_key_exists(4, $affirmation)) {
            $__affirmation = do_lang_tempcode(
                'QUIZ_AFFIRMATION_HTML',
                escape_html(is_object($affirmation[1]) ? $affirmation[1]->evaluate() : $affirmation[1]),
                escape_html(is_object($affirmation[3]) ? $affirmation[3]->evaluate() : $affirmation[3]),
                array(
                    escape_html(is_object($affirmation[2]) ? $affirmation[2]->evaluate() : $affirmation[2]),
                    escape_html(array_key_exists(4, $affirmation) ? $affirmation[4] : ''),
                )
            );
            $affirmations_to_member->attach($__affirmation);
        }
    }
    $unknowns_to_staff = new ocp_tempcode();
    foreach ($unknowns as $unknown) {
        $_unknown = do_lang('QUIZ_UNKNOWN', comcode_escape($unknown[0]), comcode_escape($unknown[1]));
        $unknowns_to_staff->attach($_unknown);
    }
    $given_answers_to_staff = new ocp_tempcode();
    foreach ($given_answers as $given_answer) {
        $_given_answer = do_lang('QUIZ_RESULT', comcode_escape($given_answer['QUESTION']), comcode_escape($given_answer['GIVEN_ANSWER']));
        $given_answers_to_staff->attach($_given_answer);
    }
    // NB: We don't have a list of what was correct because it's not interesting, only corrections/unknowns/everything.

    $passed = mixed();
    if ($minimum_percentage >= $quiz['q_percentage']) {
        $passed = true;
    } elseif ($maximum_percentage < $quiz['q_percentage']) {
        $passed = false;
    }

    return array(
        $marks,
        $potential_extra_marks,
        $out_of,
        $given_answers,
        $corrections,
        $affirmations,
        $unknowns,
        $minimum_percentage,
        $maximum_percentage,
        $marks_range,
        $percentage_range,
        $corrections_to_staff,
        $corrections_to_member,
        $affirmations_to_member,
        $unknowns_to_staff,
        $given_answers_to_staff,
        $passed,
    );
}

/**
 * Is a typed quiz answer correct?
 *
 * @param  string                       The given (typed) answer
 * @param  array                        Answer rows
 * @param  boolean                      Whether to do a strict check
 * @return boolean                      Whether it is correct
 */
function typed_answer_is_correct($given_answer, $all_answers, $strict = false)
{
    if ($strict) {
        $filtered_given_answer = trim($given_answer);
    } else {
        $filtered_given_answer = preg_replace('#[^\d\w]#', '', strtolower($given_answer));
    }
    if ($filtered_given_answer == '') {
        return false;
    }

    $has_correct = false;
    $has_incorrect = false;
    foreach ($all_answers as $a) {
        if ($strict) {
            $filtered_answer = trim(get_translated_text($a['q_answer_text']));
        } else {
            $filtered_answer = preg_replace('#[^\d\w]#', '', strtolower(get_translated_text($a['q_answer_text'])));
        }

        if (get_translated_text($a['q_answer_text']) === $filtered_given_answer) {
            return ($a['q_is_correct'] == 1);
        } // Match exactly; "===" needed to stop PHPs weird type coercion that happens even for strings

        if ((!$strict) && (levenshtein($filtered_answer, $filtered_given_answer) <= intval(strlen($filtered_answer) * 0.2))) { // Matches inexactly
            if ($a['q_is_correct'] == 1) {
                $has_correct = true;
            } else {
                $has_incorrect = true;
            }
        }
    }
    return $has_correct && !$has_incorrect;
}
