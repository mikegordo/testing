<?php

class DatabaseSeeder extends Seeder
{

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		Eloquent::unguard();

		/**
		 * Insert default users
		 */
		$password = Hash::make('12345');
		DB::table('users')->insert(
			[
				'email'      => 'm.gordo@cityads.ru',
				'password'   => $password,
				'name'       => 'Михаил Гордо',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('users')->insert(
			[
				'email'      => 'a.fitiskin@cityads.ru',
				'password'   => $password,
				'name'       => 'Артем Фитискин',
				'created_at' => date('Y-m-d H:i:s')
			]
		);

		/**
		 * Create departments
		 */
		DB::table('department')->insert(
			[
				'id'         => 1,
				'name'       => 'Администрация',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('department')->insert(
			[
				'id'         => 2,
				'name'       => 'Департамент по операциям и финансам',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('department')->insert(
			[
				'id'         => 3,
				'name'       => 'Департамент продаж',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('department')->insert(
			[
				'id'         => 4,
				'name'       => 'Департамент аккаунтинга',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('department')->insert(
			[
				'id'         => 5,
				'name'       => 'Департамент партнерских отношений',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('department')->insert(
			[
				'id'         => 6,
				'name'       => 'Арт департамент',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('department')->insert(
			[
				'id'         => 7,
				'name'       => 'Департамент производства',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('department')->insert(
			[
				'id'         => 8,
				'name'       => 'Департамент IT',
				'created_at' => date('Y-m-d H:i:s')
			]
		);
		DB::table('department')->insert(
			[
				'id'         => 9,
				'name'       => 'Проектный департамент',
				'created_at' => date('Y-m-d H:i:s')
			]
		);

		/**
		 * Create a default group
		 */
		DB::table('group')->insert(
			[
				'id'         => 1,
				'name'       => 'Отдел разработки',
				'created_at' => date('Y-m-d H:i:s')
			]
		);

		/**
		 * Create a default category
		 */
		DB::table('category')->insert(
			[
				'id'         => 1,
				'name'       => 'Стандартная',
				'created_at' => date('Y-m-d H:i:s')
			]
		);

		/**
		 * Create a test
		 */
		DB::table('test')->insert(
			[
				'id'          => 1,
				'name'        => 'Тест номер 1',
				'description' => 'Описание теста',
				'type'        => Test::TEST_TYPE_SUMMA,
				'duration'    => 600,
				'category_id' => 1,
				'user_id'     => 1,
				'created_at'  => date('Y-m-d H:i:s')
			]
		);

		/**
		 * Create a token
		 */
		DB::table('token')->insert(
			[
				'id'         => 1,
				'token'      => md5(microtime()) . md5(microtime(true)),
				'test_id'    => 1,
				'status'     => Token::TOKEN_STATUS_EMPTY,
				'created_at' => date('Y-m-d H:i:s')
			]
		);
	}

}
