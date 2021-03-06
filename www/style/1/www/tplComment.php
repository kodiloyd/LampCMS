<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website\'s Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */

/**
 * Template for generating div
 * with one comment
 * 
 * @author admin
 *
 */
class tplComment extends \Lampcms\Template\Template
{

	protected static $vars = array(
	'_id' => '', //1
	'b' => '', //2
	'ts' => '', //3
	'username' => '', //4
	'i_uid' => '', //5
	't' => '', //6
	'i_prnt' => 0, //7
	'b_owner' => '', //8
	'edit_delete' => '', //9
	'i_likes' => '', // 10
	'e' => '' //11
	);


	protected static $tpl = '
	<div class="fl cb com_wrap reply-%7$s" id="comment-%1$s">
	    <a name="c%1$s"></a>
		<div class="com_1 fl cb1">
			<div class="fl com_left">
				<div class="fr com_like votebtns">
					<a id="c_like_%1$s" title="I Like this comment!" class="ajax thumbup c_like" href="#">Good</a>
				</div>
				<div class="c_likes fr">%10$s</div>
			</div>
			<div class="fl com_b">%2$s</div>
		</div>
		<div class="com_i fl cb1">
			<div class="fl com_flag">
				<span class="ico flag ajax" id="cflag_%1$s" title="Flag this comment as inappropriate">flag</span>
			</div>
			<div class="com_tools controls uid-%5$s" id="res_%1$s">
				<div class="com_auth usr usr_%5$s fl"><a href="/users/%5$s/" class="iu usr-%5$s commentor%8$s">%4$s</a></div>	
				<div title="%3$s" class="com_ts ts fl">%6$s</div>
				%11$s
				%9$s
			</div>	
		</div>
	</div>';
}
