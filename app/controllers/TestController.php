<?php

/**
 * Class TestController
 *
 * @author Mike Gordo <m.gordo@cityads.ru>
 */
class TestController extends BaseController
{
	/**
	 * Выводим вопрос пользователю
	 *
	 * @return mixed
	 */
	public function indexAction()
	{
		Assets::reset()->add('main');

		$token = $this->getToken();
		if (!$token) {
			return Redirect::route('base');
		}

		if ($token->status != Token::TOKEN_STATUS_STARTED) {
			return Redirect::route('token.index', ['token' => $token->token]);
		}

		if (!$this->isTokenValid($token)) {
			Session::forget('token_string');
			$token->status = Token::TOKEN_STATUS_EXPIRED;
			$token->save();

			return Redirect::route('info')->with('message', 'Время теста истекло');
		}

		/**
		 * Тут получаем следующий вопрос, выводим его пользователю
		 */
		$questions = $token->test->questions;
		$results   = Result::where('token', $token->token)->where('test_id', $token->test->id)->get();

		if (count($results)) {
			if (count($results) == count($questions)) {
				/**
				 * Ответы есть на все вопросы
				 */
				Session::forget('token_string');

				return Redirect::route('info')->with('message', 'Тест завершен. Вы ответили на все вопросы.');
			} else {
				/**
				 * Убирает вопросы на которые есть ответы
				 */
				$answered = [];
				foreach ($results as $result) {
					$answered[] = $result->question->id;
				}

				foreach ($questions as $key => $q) {
					if (in_array($q->id, $answered)) {
						unset($questions[$key]);
					}
				}
			}
		}

		/**
		 * Перемешаем вопросы, сгенерируем кодовые суммы
		 */
		$questions_ = [];
		foreach ($questions as $q) {
			$questions_[] = $q->id;
		}

		shuffle($questions_);

		if ($questionId = Session::get('question_id', false)) {
			$question = Question::find($questionId);
		} else {
			$question = Question::find($questions_[0]);
		}

		$answers_ = $question->answers;
		$answers  = [];
		Session::forget('test_answers');
		$ta = [];

		foreach ($answers_ as $key => $answer) {
			$a               = $answer->withHash();
			$ta[$answer->id] = $a->hash;
			$answers[]       = $a;
		}

		Session::put('test_answers', $ta);
		Session::put('question_id', $question->id);
		shuffle($answers);

		return View::make('test.index', [
			'question' => $question,
			'answers'  => $answers,
			'token'    => $token
		]);
	}

	/**
	 * Сохраняем ответ пользователя
	 *
	 * @return mixed
	 */
	public function storeAction()
	{
		$token = $this->getToken();
		if (!$token) {
			return Redirect::route('info')
				->with('message', 'Токен не найден');
		}

		if ($token->status != Token::TOKEN_STATUS_STARTED) {
			return Redirect::route('token.index', ['token' => $token->token]);
		}

		if (!$this->isTokenValid($token)) {
			Session::forget('token_string');
			$token->status = Token::TOKEN_STATUS_EXPIRED;
			$token->save();

			return Redirect::route('info')->with('message', 'Время теста истекло, ответ не засчитан');
		}

		$questionId  = Session::get('question_id', false);
		$testAnswers = Session::get('test_answers', []);

		if (!$questionId || !is_numeric($questionId)) {
			return Redirect::route('info')->with('message', 'Ошибка данных сессии.');
		}

		$question = Question::find((int)$questionId);
		if (!$question) {
			return Redirect::route('info')->with('message', 'Ошибка данных сессии. Вопрос не найден.');
		}

		$data = Input::all();

		$result = new Result();

		$result->token       = $token->token;
		$result->test_id     = $token->test->id;
		$result->question_id = (int)$questionId;
		$result->q_text      = $question->text;
		$result->q_image     = $question->image;

		/**
		 * Обработаем ответ в зависимости от типа
		 */
		switch ($question->type) {
			case Question::TYPE_RADIO:
				if (!isset($data['answer']) || !$data['answer']) {
					return Redirect::route('info')->with('message', 'Ожидается ответ.');
				}
				foreach ($testAnswers as $id => $hash) {
					if ($hash == $data['answer']) {
						$answer = Answer::find($id);
					}
				}
				if (!$answer) {
					return Redirect::route('info')->with('message', 'Такой ответ не найден.');
				}
				$result->a_text     = $answer->text;
				$result->a_image    = $answer->image;
				$result->is_correct = $answer->is_correct;
				$result->weight     = $answer->weight;
				break;
			case Question::TYPE_CHECKBOX:
				$answer = isset($data['answer']) ? $data['answer'] : []; // has to be an array
				if (!is_array($answer)) {
					return Redirect::route('info')->with('message', 'Ответ должен быть массивом.');
				}

				$answer_ = $answer; // we need to find real answer ids
				$answer  = [];
				foreach ($testAnswers as $id => $hash) {
					foreach ($answer_ as $a_) {
						if ($hash == $a_) {
							$answer[] = Answer::find($id)->id;
						}
					}
				}
				if (!empty($answer))
					$answers = DB::table('answer')->whereIn('id', $answer)->get(); // какие ответы выбрал пользователь
				else
					$answers = [];

				$isThereACorrectAnswer = false;
				$correctNum            = 0;
				// есть ли хоть один правильный ответ?
				foreach ($question->answers as $ans) {
					if ($ans->is_correct) {
						$isThereACorrectAnswer = true;
						$correctNum++;
					}
				}

				$result->a_text = '';

				if (!$isThereACorrectAnswer && count($answers) > 0) {
					// правильных ответов нет, но пользователь что-то выбрал
					$result->is_correct = false;
				} elseif (!$isThereACorrectAnswer && count($answers) == 0) {
					$result->is_correct = true;
				} elseif ($isThereACorrectAnswer && count($answers) == 0) {
					$result->is_correct = false;
				} elseif ($isThereACorrectAnswer && count($answers) > 0 && count($answers) != $correctNum) {
					$result->is_correct = false;
				} elseif ($isThereACorrectAnswer && count($answers) > 0 && count($answers) == $correctNum) {
					// проверим, те ли ответы выбрал пользователь
					$result->is_correct = true;
					$result->weight = 0;
					foreach ($question->answers as $ans) {
						if (!$ans->is_correct) continue;
						if (!in_array($ans->id, $answer)) {
							$result->is_correct = false;
						}
						$result->a_text .= $ans->text . ';';
						$result->a_image = $ans->image;
						$result->weight += $ans->weight;
					}
					if (!$result->is_correct)
						$result->weight = 0;
				}
				break;
			case Question::TYPE_STRING:
				if (!isset($data['answer']) || !$data['answer']) {
					$result->a_text     = '';
					$result->is_correct = false;
				} else {
					$result->a_text     = trim($data['answer']);
					$result->is_correct = true;
					$result->weight     = 1;
				}
				break;
		}

		$result->save();

		Session::forget('question_id');

		return Redirect::route('test.index');
	}

	/**
	 * Ajax запрос на валидность токена
	 *
	 * @return mixed
	 */
	public function checkTime()
	{
		$token = $this->getToken();
		if (!$token) {
			return Response::json(['error' => 'Токен не найден'], 400);
		}

		$started  = $token->start;
		$time     = time() - $started;
		$duration = $token->test->duration;

		return Response::json(
			[
				'time'     => $time,
				'duration' => $duration,
				'status'   => $time < $duration
			]
		);
	}
}