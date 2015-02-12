<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		quizzes
 */

class Hook_Preview_quiz
{

	/**
	 * Find whether this preview hook applies.
	 *
	 * @return array			Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
	 */
	function applies()
	{
		$applies=(get_param('page','')=='cms_quiz') && ((get_param('type','')=='ad') || (get_param('type','')=='_ed'));
		return array($applies,NULL,false);
	}

	/**
	 * Standard modular run function for preview hooks.
	 *
	 * @return array			A pair: The preview, the updated post Comcode
	 */
	function run()
	{
		require_code('quiz');

		$questions=array();

		$text=post_param('text');
		$type=post_param('type');

		$_qs=explode(chr(10).chr(10),$text);
		$qs=array();
		foreach ($_qs as $q)
		{
			$q=trim($q);
			if ($q!='') $qs[]=$q;
		}
		$num_q=0;

		$qs2=array();
		foreach ($qs as $i=>$q)
		{
			$_as=explode(chr(10),$q);
			$as=array();
			foreach ($_as as $a)
			{
				if ($a!='') $as[]=$a;
			}
			$q=array_shift($as);
			$matches=array();
			//if (preg_match('#^(\d+)\)?(.*)#',$q,$matches)===false) continue;
			if (preg_match('#^(.*)#',$q,$matches)===false) continue;
			if (count($matches)==0) continue;

			$implicit_question_number=$i;//$matches[1];

			$qs2[$implicit_question_number]=$q.chr(10).implode(chr(10),$as);
		}
		ksort($qs2);

		foreach (array_values($qs2) as $i=>$q)
		{
			$_as=explode(chr(10),$q);
			$as=array();
			foreach ($_as as $a)
			{
				if ($a!='') $as[]=$a;
			}
			$q=array_shift($as);
			$matches=array();
			//if (preg_match('#^(\d+)\)?(.*)#',$q,$matches)===false) continue;
			if (preg_match('#^(.*)#',$q,$matches)===false) continue;
			if (count($matches)==0) continue;
			$question=trim($matches[count($matches)-1]);
			$long_input_field=(strpos($question,' [LONG]')!==false)?1:0;
			$question=str_replace(' [LONG]','',$question);
			$num_choosable_answers=(strpos($question,' [*]')!==false)?count($as):((count($as)>0)?1:0);
			$question=str_replace(' [*]','',$question);
			$required=(strpos($question,' [REQUIRED]')!==false)?1:0;
			$question=str_replace(' [REQUIRED]','',$question);

			// Now we add the answers
			$answers=array();
			foreach ($as as $x=>$a)
			{
				$is_correct=((($x==0) && (strpos($qs2[$i],' [*]')===false) && ($type!='SURVEY')) || (strpos($a,' [*]')!==false))?1:0;
				$a=str_replace(' [*]','',$a);

				if (substr($a,0,1)==':') continue;

				$answers[]=array(
					'id'=>$x,
					'q_answer_text'=>$a,
					'q_is_correct'=>$is_correct,
				);
			}

			$questions[]=array(
				'id'=>$i,
				'q_long_input_field'=>$long_input_field,
				'q_num_choosable_answers'=>$num_choosable_answers,
				'q_question_text'=>$question,
				'answers'=>$answers,
				'q_required'=>$required,
			);

			$num_q++;
		}

		$preview=render_quiz($questions);

		return array(do_template('FORM',array('SUBMIT_NAME'=>'','TEXT'=>'','URL'=>'','HIDDEN'=>'','FIELDS'=>$preview)),NULL);
	}

}


