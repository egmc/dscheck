<?php
namespace DsCheck;

use \Swift_Mailer;
use \Swift_MailTransport;
use \Swift_Message;
use \Exception;

/**
 * DsCheck
 *
 * easy webservice status checker
 *
 * @author EG
 */
class DsCheck {

	protected $result_list = array();
	protected $check_list = array();
	protected $silent_mode = false;
	protected $result_file_path = '';

	protected $result_str = '';

	protected $mailer;
	protected $message;
	
	protected $mail_to_list;
	protected $mail_from;

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 * @param Qdmail $mailer
	 * @throws Exception
	 */
	public function __construct($settings) {
		if (!is_array($settings)) {
			// 設定ファイルエラー
			throw new Exception ("settingparam is not an array");
		}

		$this->parseSettings($settings);

		// メーラーのセットアップ
		$mailer = Swift_Mailer::newInstance(Swift_MailTransport::newInstance());

		if (file_exists($this->result_file_path)) {
			// ファイルが存在
			if (is_dir($this->result_file_path)) {
				throw new Exception ("settingparam is not a file");
			}
			if (!is_writable($this->result_file_path)) {
				throw new Exception("file is not set");
			}
			if (!is_readable($this->result_file_path)) {
				throw new Exception("file is not readble");
			}
			$this->result_list = unserialize(file_get_contents($this->result_file_path));
		} else {
			if (!is_writable(dirname($this->result_file_path))) {
				throw new Exception("dir" .$this->result_file_path ." is not writable");
			}
		}

		$this->mailer = $mailer;

	}

	/**
	 * 設定情報のパース
	 *
	 * @param array $settings
	 * @throws Exception
	 */
	protected function parseSettings($settings) {
		if (isset($settings['silent_mode'])) {
			$this->silent_mode = (bool)$settings['silent_mode'];
		}
		if (!isset($settings['result_file_path'])) {
			throw new Exception("result file path is not set");
		}

		// パス設定
		if ($settings['result_file_path'][0] == "/") {
			$this->result_file_path = $settings['result_file_path'];
		} else {
			$this->result_file_path =  __DIR__ . "/../../" . $settings['result_file_path'];
		}

		if (!is_array($settings['check_list'])) {
			throw new Exception("check_list is not an array");
		}
		foreach ($settings['check_list'] as $checkrow) {
			if (!isset($checkrow['url'])) {
				throw new Exception("url is not set");
			}
			if (!isset($checkrow['check_string'])) {
				throw new Exception("check_string is not set");
			}
		}
		$this->check_list = $settings['check_list'];

		$this->mail_from = $settings['mail_from'];

		if (!is_array($settings['mail_to_list'])) {
			throw new Exception("mail_to_list is not an array");
		}
		$this->mail_to_list = $settings['mail_to_list'];

	}

	/**
	 * do check
	 */
	public function run() {
		foreach ($this->check_list as $check) {
			$reason = "";
			$is_ok = false;
			$url = $check['url'];
			$check_str = $check['check_string'];
			$name = $check['name'];

			//var_dump($check);

			$send_mail = false;
			$body_text = "";

			$this->out("--- start $name check --- ");

			$response = file_get_contents($url);
			if($response === false) {
				// リクエストエラー
				$reason = "リクエストエラー";

				$this->out("http request faied ");
			} else {
				// レスポンスヘッダチェック
				list(,$rescode,) = explode(' ', $http_response_header[0]);
				if ($rescode[0] == "5" || $rescode[0] == "4") {
					// 不正なレスポンスコード
					$reason = "不正なステータスコード $rescode";

					$this->out("invalid status code $rescode");
				} else {
					// 文字列チェック
					if (mb_strpos($response, $check_str) === false) {
						$reason = "コンテンツに文字列　$check_str が含まれていません";
						$this->out("checkstring $check_str not found");
					} else {
						$is_ok = true;
						$body_text .= "リクエストOK";
						$this->out("status ok");
					}
				}
			}
			if (!isset($this->result_list[$url])) {
				$body_text .= "監視スタート\n";
				$send_mail = true;
			} else {
				if ($this->result_list[$url] != $is_ok) {
					$send_mail = true;

				}
			}
			//結果をセット
			$this->result_list[$url] = $is_ok;
			//メール送信判定
			if ($send_mail) {
				$title = ($is_ok) ? "[Clear]" : "[Down]";
				$title .= $name;

				$body_text .= $reason;

				$this->out($title);
				$this->out($body_text);
				
				$this->message = \Swift_Message::newInstance();
				foreach ($this->mail_to_list as $mail_to) {
					$this->message->setTo($mail_to);
				}
				$this->message->setFrom($this->mail_from);
				
				$this->message->setSubject($title);
				$this->message->setBody($body_text);
				$this->mailer->send($this->message);
			}

		}
		// 結果を保存
		file_put_contents($this->result_file_path, serialize($this->result_list));
	}

	/**
	 * 標準出力
	 *
	 * 標準出力を実行。
	 * サイレントモード時は出力しない。
	 *
	 * @param string $str
	 */
	protected function out($str) {
		if(!$this->silent_mode) {
			echo "$str\n";
		}
	}
}