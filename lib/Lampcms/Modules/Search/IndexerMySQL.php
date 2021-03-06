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


namespace Lampcms\Modules\Search;

use \Lampcms\Interfaces\Indexer;
use \Lampcms\Registry;

/**
 * Wrapper class for adding Questions
 * and possible Answers to the search index
 * Currently works with MySQL as Index provider
 *
 * In the Future if we switch to Lucene we will
 * rewrite or extend this class
 * to make use of Lucene instead
 *
 * All objects that use this class will
 * not have to be changed because all signatures will
 * remain the same, regardless of whant Index provider
 * we may have
 *
 *
 * @author Dmitri Snytkine
 *
 */
class IndexerMySQL implements Indexer
{
	/**
	 * Registry Object
	 * @var object of type Registry
	 */
	protected $oRegistry;

	public function __construct(\Lampcms\Registry $oRegistry){
		$this->oRegistry = $oRegistry;
	}


	/**
	 * Record title, intro, qid, url, date
	 * into QUESTIONS_TITLE mysql table
	 * the purpose of this is to use
	 * full-text index capabilities
	 * of MYSQL on the question title,
	 * so we can easily find 'similar'
	 * questions based on titles and can
	 * also use this for site search feature
	 * to some extent. Cool
	 *
	 * @param Question $oQuestion
	 */
	public function indexQuestion(\Lampcms\Question $oQuestion){
		if(!extension_loaded('pdo_mysql')){
			d('pdo_mysql not loaded ');

			return $this;
		}

		$res       = false;
		$qid       = $oQuestion['_id'];
		$title     = $oQuestion['title'];
		$body      = $oQuestion['b'];
		$url       = $oQuestion['url'];
		$intro     = $oQuestion['intro'];
		$uid       = $oQuestion['i_uid'];
		$username  = $oQuestion['username'];
		$ulink     = $oQuestion['ulink'];
		$avatar    = $oQuestion['avtr'];
		$tags_c    = $oQuestion['tags_c'];
		$tags_html = $oQuestion['tags_html'];

		d($qid.' title: '. $title. ' url: '. $url.' intro: '.$intro.' tags_c: '.$tags_c);

		$sql = 'INSERT INTO question_title
		(
		qid, 
		title,
		q_body, 
		url, 
		intro, 
		uid,
		username,
		userlink,
		avtr,
		tags_c,
		tags_html)
		VALUES (
		:qid, 
		:qtitle, 
		:qbody,
		:qurl, 
		:qintro, 
		:uid,
		:username,
		:userlink,
		:avatar,
		:tags_c,
		:tags_html)';


		try{
			$sth = $this->oRegistry->Db->makePrepared($sql);
			$sth->bindParam(':qid', $qid, \PDO::PARAM_INT);
			$sth->bindParam(':qtitle', $title, \PDO::PARAM_STR);
			$sth->bindParam(':qbody', $body, \PDO::PARAM_STR);
			$sth->bindParam(':qurl', $url, \PDO::PARAM_STR);
			$sth->bindParam(':qintro', $intro, \PDO::PARAM_STR);
			$sth->bindParam(':tags_c', $tags_c, \PDO::PARAM_STR);
			$sth->bindParam(':tags_html', $tags_html, \PDO::PARAM_STR);
			$sth->bindParam(':uid', $uid, \PDO::PARAM_INT);
			$sth->bindParam(':username', $username, \PDO::PARAM_STR);
			$sth->bindParam(':userlink', $ulink, \PDO::PARAM_STR);
			$sth->bindParam(':avatar', $avatar, \PDO::PARAM_STR);

			$res = $sth->execute();
		} catch (\Exception $e){

			$err = ('Exception: '.get_class($e).' Unable to insert into mysql because: '.$e->getMessage().' Err Code: '.$e->getCode().' trace: '.$e->getTraceAsString());
			d('mysql error: '.$err);

			if('42S02' === $e->getCode()){
				if(true === TitleTagsTable::create($this->oRegistry)){
					$this->indexTitle($oQuestion);
				}
			} else {
				throw $e;
			}

		}
		d('res: '.$res);

		return $this;
	}


	/**
	 * Remove record for one Question from the
	 * Index
	 *
	 * @param Question $oQuestion
	 * @return object $this
	 */
	public function removeQuestion(\Lampcms\Question $oQuestion){
		if(!extension_loaded('pdo_mysql')){
			d('pdo_mysql not loaded ');

			return $this;
		}

		$qid   = $oQuestion->offsetGet('_id');
		$sql = 'DELETE FROM question_title WHERE qid = :qid';
		d('about to remove question with qid: '.$qid);

		try{
			$sth = $sth = $this->oRegistry->Db->makePrepared($sql);
			$sth->bindParam(':qid', $qid, \PDO::PARAM_INT);
			$res = $sth->execute();
			d('res: '.$res);
		} catch(\Exception $e){
			$err = ('Exception: '.get_class($e).' Unable to delete question because: '.$e->getMessage().' Err Code: '.$e->getCode().' trace: '.$e->getTraceAsString());
			d('mysql error: '.$err);
		}

		return $this;
	}


	/**
	 * When question is edited in any way we will
	 * run this method to also update
	 * the index.
	 *
	 * @param Question $oQuestion
	 */
	public function updateQuestion(\Lampcms\Question $oQuestion){

		if(!extension_loaded('pdo_mysql')){
			d('pdo_mysql not loaded ');

			return $this;
		}

		$res   = false;
		$qid   = $oQuestion->offsetGet('_id');
		$title = $oQuestion->offsetGet('title');
		$url   = $oQuestion->offsetGet('url');
		$intro = $oQuestion->offsetGet('intro');
		$username = $oQuestion['username'];
		$ulink = $oQuestion['ulink'];
		$avatar = $oQuestion['avtr'];
		$tags_c = $oQuestion['tags_c'];
		$tags_html = $oQuestion['tags_html'];
		$body = $oQuestion['body'];

		d($qid.' title: '. $title. ' url: '. $url.' intro: '.$intro.' tags_c: '.$tags_c);

		$sql = 'UPDATE question_title
		SET 
		title = :qtitle,
		q_body = :qbody,
		url = :qurl, 
		intro = :qintro, 
		username = :username,
		userlink = :userlink,
		avtr = :avatar,
		tags_c = :tags_c,
		tags_html = :tags_html
		WHERE qid = :qid';


		try{
			$sth = $this->oRegistry->Db->makePrepared($sql);
			$sth->bindParam(':qid', $qid, \PDO::PARAM_INT);
			$sth->bindParam(':qtitle', $title, \PDO::PARAM_STR);
			$sth->bindParam(':qbody', $body, \PDO::PARAM_STR);
			$sth->bindParam(':qurl', $url, \PDO::PARAM_STR);
			$sth->bindParam(':qintro', $intro, \PDO::PARAM_STR);
			$sth->bindParam(':tags_c', $tags_c, \PDO::PARAM_STR);
			$sth->bindParam(':tags_html', $tags_html, \PDO::PARAM_STR);
			$sth->bindParam(':username', $username, \PDO::PARAM_STR);
			$sth->bindParam(':userlink', $ulink, \PDO::PARAM_STR);
			$sth->bindParam(':avatar', $avatar, \PDO::PARAM_STR);

			$res = $sth->execute();
		} catch (\Exception $e){

			$err = ('Exception: '.get_class($e).' Unable to insert into mysql because: '.$e->getMessage().' Err Code: '.$e->getCode().' trace: '.$e->getTraceAsString());
			d('mysql error: '.$err);

			if('42S02' === $e->getCode()){
				if(true === TitleTagsTable::create($this->oRegistry)){
					$this->indexTitle($oQuestion);
				}
			} else {
				throw $e;
			}

		}
		d('res: '.$res);

		return $this;
	}


	/**
	 * Remove record belonging to one user
	 *
	 * @param int $uid id of user (value of _id from USERS collection)
	 * @return object $this
	 */
	public function removeByUserId($uid){
		if(!extension_loaded('pdo_mysql')){
			d('pdo_mysql not loaded ');

			return $this;
		}

		$uid   = (int)$uid;
		$sql = 'DELETE FROM question_title WHERE uid = :uid';
		d('about to remove question with uid: '.$uid);

		try{
			$sth = $sth = $this->oRegistry->Db->makePrepared($sql);
			$sth->bindParam(':uid', $uid, \PDO::PARAM_INT);
			$res = $sth->execute();
			d('res: '.$res);
		} catch(\Exception $e){
			$err = ('Exception: '.get_class($e).' Unable to delete question because: '.$e->getMessage().' Err Code: '.$e->getCode().' trace: '.$e->getTraceAsString());
			d('mysql error: '.$err);
		}

		return $this;
	}

}
