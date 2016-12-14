<?php

/**
* Возвращает инфу из моего профиля
*/
class api_users_me extends rMyAPIModule
{
	
	function Run()
	{
		if(!$this->app->user->authed())
			throw new rNotAuthorized('Authorization needed', 401);
			

		$data = $this->app->user->getData();
		$data['stats'] = $this->app->user->getStats();
		$data['info'] = $this->app->user->getInfo();

		unset($data[PASS_FIELD], $data['salt']);

		return $data;
	}
}