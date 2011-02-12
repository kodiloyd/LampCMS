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
 *    the website's Questions/Answers functionality is powered by lampcms.com
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


namespace Lampcms;

/**
 * Class represents logged in User
 *
 * @author Dmitri Snytkine
 *
 */
class User extends MongoDoc implements Interfaces\RoleInterface, Interfaces\User, Interfaces\TwitterUser, Interfaces\FacebookUser
{

	/**
	 * Special flag indicates that user has
	 * just registered. This flag stays only during
	 * the session, so for the whole duraction of the session
	 * we know that this is a new user
	 * @var bool
	 */
	protected $bNewUser = false;

	/**
	 * Path to avatar image
	 * used for memoization
	 *
	 * @var string
	 */
	protected $avtrSrc;

	/**
	 * Factory method
	 *
	 * @todo MUST pass Registry here!
	 *
	 * @param array $a
	 *
	 * @return object of this class
	 */
	public static function factory(Registry $oRegistry, array $a = array())
	{
		$o = new static($oRegistry, 'USERS', $a);
		$o->applyDefaults();

		return $o;
	}

	public function __get($name)
	{
		if('id' === $name){

			return $this->getUid();
		}

		return $this->offsetGet($name);
	}

	/**
	 * Checks to see if profile exists for the user
	 *
	 * @return bool true if profile exists
	 */
	public function isProfileSet()
	{
		return $this->checkOffset('profile');
	}


	/**
	 * Getter for userID (value of USER.id)
	 *
	 * @return int value of userid (value of USER.id)
	 */
	public function getUid()
	{
		if (true !== $this->checkOffset($this->keyColumn)) {

			return 0;
		}

		return (int)$this->offsetGet($this->keyColumn);
	}


	/**
	 *
	 * Get link to user's external url
	 *
	 * @return html of link to user's external page
	 */
	public function getUrl(){
		$url = $this->offsetGet('url');
		if(empty($url)){
			return '';
		}

		return '<a rel="nofollow" href="'.$url.'">'.$url.'</a>';
	}


	/**
	 * Check to see if user is registered
	 * or guest user
	 *
	 * Guest always has uid === 0
	 *
	 * @return bool true if user is a guest,
	 * false if registered user
	 */
	public function isGuest(){

		return 0 === $this->getUid();
	}

	/**
	 * Get full name of user
	 * by concatinating first name, middle name, last name
	 * @return string full name
	 */
	public function getFullName()
	{
		return $this->offsetGet('fn').' '.$this->offsetGet('mn').' '.$this->offsetGet('ln');
	}

	/**
	 * Get string to display as user name
	 * preferrably it's a full name, but if
	 * user has not yet provided it, then
	 * user just 'username'
	 *
	 * @return string value to display on welcome block
	 */
	public function getDisplayName()
	{
		$ret = $this->getFullName();
		/**
		 * Must trim, otherwise
		 * we can have a string with just 2 spaces, which
		 * is not considered empty.
		 */
		$ret = trim($ret);
		if(!empty($ret)){
			d('returning full name: '.$ret);

			return $ret;
		}

		$ret = $this->offsetGet('username');
		d('returning full name: '.$ret);

		return $ret;
	}

	public function __set($name, $val)
	{
		$this->offsetSet($name, $val);
	}

	/**
	 * Return HTML code for avatar image (with full path)
	 * @param string $sSize type of avatar: large, medium, tiny
	 * @param bool $boolNoCache if true, then add a timestamp to url, making
	 * browser not to use cached version and to get a fresh new one
	 *
	 * @return string the HTML code for image src
	 */
	public function getAvatarImgSrc($sSize = 'medium', $noCache = false)
	{

		$width = AVATAR_SQUARE_SIZE;

		$strAvatar = '<img src="' . $this->getAvatarSrc($noCache) . '" class="imgAvatar" width="' . $width . '" height="'.$width.'" border="0" alt="avatar"/>';

		return $strAvatar;

	}

	/**
	 * Get only the http path to avatar without
	 * any of the img tag.
	 *
	 * @return string path to avatar image medium size
	 */
	public function getAvatarSrc($noCache = false){

		if(!isset($this->avtrSrc)){

			$srcAvatar = trim($this->offsetGet('avatar'));
			if(empty($srcAvatar)){
				$srcAvatar =  trim($this->offsetGet('avatar_external'));
			}

			if(empty($srcAvatar)){
				$srcAvatar = 'user.jpg';
			}

			/**
			 * Path to avatar may be a relative path
			 * if this is our own avatar
			 * or absolute path if this is an external avatar
			 * like avatar from Twitter or FC or GFC
			 *
			 */
			$this->avtrSrc = (0 === strncmp($srcAvatar, 'http', 4)) ? $srcAvatar : AVATAR_IMG_SITE.PATH_WWW_IMG_AVATAR_SQUARE.$srcAvatar;

			if (true === $noCache) {
				$this->avtrSrc .= '?id=' . microtime(true); // helps browser to NOT cache this image
			}

		}

		return $this->avtrSrc;
	}

	public function getProfileUrl(){
		return '/users/'.$this->getUid().'/'.$this->offsetGet('username');
	}

	/**
	 */
	protected function getAvatarPath()
	{
		$base = AVATAR_IMG_SITE;

	}

	/**
	 * Get profile object for this user
	 * set in as instance variable if not already there
	 * for memoization
	 *
	 * @todo return default profile is one does not exist
	 *
	 * @return array
	 */
	public function getProfile()
	{
		if(!$this->isProfileSet()){
			// need to return some type of default profile.....
		}

		return $this->offsetGet('profile');
	}

	/**
	 * Get preferences object
	 * for this user
	 *
	 * @todo return default prefs if one does not
	 * exist in user
	 *
	 * @return array
	 *
	 */
	public function getPrefs()
	{
		if(!$this->checkOffset('prefs')){
			//
		}

		return $this->offsetGet('prefs');
	}


	/**
	 * Returns array of this array merged with
	 * array of profile.
	 * The result is array of all fields from USERS and PROFILE table
	 * for this user.
	 *
	 * This is convenient method to get array for editing of profile
	 *
	 * Important: it contains all columns from 2 tables, even those
	 * that should not always be shown to other users or even
	 * to this user, like data that user may not even be aware that we store
	 * about him/her
	 * So, be very carefull not to show all fields, use some sort of
	 * custom filters.
	 *
	 * @todo change this to return profile
	 *
	 * @return unknown_type
	 */
	public function getFullProfileArray()
	{
		return $this->getMerged($this->getProfile());
	}


	/**
	 * Implements Zend_Acl_Role_Interface
	 * (non-PHPdoc)
	 *
	 * @todo check if needs changing
	 *
	 * @see classes/Zend/Acl/Role/Zend_Acl_Role_Interface#getRoleId()
	 * @return string the value of user_group_id of user which
	 * serves as the role name in Zend_Acl
	 */
	public function getRoleId()
	{
		$role = $this->offsetGet('role');

		return (!empty($role)) ? $role : 'guest';
	}


	/**
	 * Get twitter user_id of user
	 * @return int
	 */
	public function getTwitterUid()
	{
		return $this->offsetGet('twitter_uid');
	}

	public function getTwitterUrl(){
		$user = $this->getTwitterUsername();
		if(empty($user)){
			return '';
		}

		return '<a rel="nofollow" href="http://twitter.com/'.$user.'">@'.$user.'</a>';
	}

	/**
	 * Get oAuth token
	 * that we got from Twitter for this user
	 * @return string
	 */
	public function getTwitterToken()
	{
		return $this->offsetGet('oauth_token');
	}

	/**
	 * Get oAuth sercret that we got for this user
	 * @return string
	 */
	public function getTwitterSecret()
	{
		return $this->offsetGet('oauth_token_secret'); //twitter_token
	}

	public function getTwitterUsername()
	{
		return $this->offsetGet('twtr_username');
	}

	/**
	 * Empty the values of oauth_token
	 * and oauth_token_secret
	 * and save the data
	 *
	 * @return object $this
	 */
	public function revokeOauthToken()
	{
		d('Revoking user OauthToken');
		$this->offsetUnset('oauth_token');
		$this->offsetUnset('oauth_token_secret');
		$this->save();

		/**
		 * Since oauth_token and oauth_secret are not store in
		 * USER table
		 * we actually need to update the USERS_TWITTER Collection
		 */
		$coll = $this->oRegistry->Mongo->getCollection('USERS_TWITTER');
		$coll->remove(array('_id' => $this->getTwitterUid()));
		d('revoked Twitter token for user: '.$uid);

		return $this;
	}

	/**
	 * Unsets the fb_id from the object, therefore
	 * marking user as NOT connected to facebook
	 * account
	 *
	 * @return object $this;
	 */
	public function revokeFacebookConnect()
	{
		/**
		 * Instead of offsetUnset we do
		 * offsetSet and set to null
		 * This is necessary in case user
		 * does not have these keys yet,
		 * in which case offsetUnset will raise error
		 */
		$this->offsetSet('fb_id', null);
		$this->offsetSet('fb_token', null);
		$this->save();

		$coll = $this->oRegistry->Mongo->getCollection('USERS_FACEBOOK');
		$coll->update(array('i_uid' => $this->getUid()), array('$set' => array('access_token' => '')));
		d('revoked FB token for user: '.$uid);

		return $this;
	}

	/**
	 * (non-PHPdoc)
	 * @see Lampcms\Interfaces.FacebookUser::getFacebookUid()
	 */
	public function getFacebookUid()
	{
		return (string)$this->offsetGet('fb_id');
	}


	public function getFacebookUrl(){
		$url = $this->offsetGet('fb_url');
		if(empty($url)){
			return '';
		}

		return '<a rel="nofollow" href="'.$url.'">'.$url.'</a>';
	}

	/**
	 * (non-PHPdoc)
	 * @see Lampcms\Interfaces.FacebookUser::getFacebookToken()
	 */
	public function getFacebookToken(){
		return $this->offsetGet('fb_token');
	}

	public function __toString()
	{
		return 'object of type '.$this->getClass().' for userid: '.$this->getUid();
	}

	/**
	 * Setter for bNewUser
	 *
	 * @return object $this
	 */
	public function setNewUser()
	{
		$this->bNewUser = true;

		return $this;
	}

	/**
	 * Getter for this->bNewUser
	 *
	 * @return bool true indicates that this object
	 * represents a new user
	 */
	public function isNewUser()
	{
		return $this->bNewUser;
	}

	/**
	 * Unique hash code for one user
	 * This is useful for generating etag of cache headers
	 * User is considered the same user if
	 * Array of data is the same as well as class name
	 * and bNewUser status.
	 * So if user changed something that may result in
	 * different info on welcome block
	 * or different permissions for the user
	 * like name or avatar
	 * or usergroup id
	 * then cached page should not be shown.
	 *
	 * @return string unique to each user
	 *
	 */
	public function getUserHash(){
		$a = $this->getArrayCopy();

		return hash('md5', json_encode($a).$this->getClass().$this->bNewUser);
	}


	public function setTimezone(){
		$tz = $this->offsetGet('timezone');
		if(!empty($tz)){
			if (false === @date_default_timezone_set( $tz )) {
				d( 'Error: wrong value of timezone: '.$tz );
			}
		}

		return $this;
	}

	/**
	 * Change the 'role' to 'registered' if
	 * user has 'unactivated' or 'unactivated_external' role
	 *
	 * @return object $this
	 */
	public function activate(){
		$role = $this->offsetGet('role');

		if(('unactivated' === $role) || ('unactivated_external' === $role)){
			$this->offsetSet('role', 'registered');
		}

		return $this;
	}


	/**
	 * Change reputation score
	 * Makes sure new score can never go lower than 1
	 * @param int $iPoints
	 *
	 * @return object $this
	 */
	public function setReputation($iPoints){
		$iRep = $this->offsetGet('i_rep');
		$iNew = max(array(1, $iRep + (int)$iPoints));

		$this->offsetSet('i_rep', $iNew);

		return $this;
	}


	/**
	 *
	 * Get reputation score of user
	 * @return int reputation of user, with minimum of 1
	 */
	public function getReputation(){

		return max(1, $this->offsetGet('i_rep'));
	}

	
	public function getLocation(){
		$country = $this->offsetGet('country');
		$state = $this->offsetGet('state');
		$city = $this->offsetGet('city');
		
		$ret = '';
		if(!empty($city) && !empty($state)){
			$ret .= $city.', '.$state;
		}
		
		if(!empty($country)){
				$ret .= ' '.$country;
		}
		
		return $ret;
	}

	/**
	 * Update i_lm_ts timestamp
	 *
	 *
	 * @return object $this
	 */
	public function setLastActive(){
		$this->offsetSet('i_lm_ts', time());
		//$this->save();

		return $this;
	}

}
