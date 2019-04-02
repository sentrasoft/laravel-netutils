<?php

namespace Sentrasoft\Netutils;

use Illuminate\Support\ServiceProvider;

class NetutilsServiceProvider extends ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
	}

	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('netutils',function($app){
			return new Tools;
		});
	}
}
