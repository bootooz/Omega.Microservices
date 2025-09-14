<?php

namespace App\Skiwatch\Api;

use Illuminate\Http\Request;

class Table
{
    public static function finish(Request $request)
    {
        echo $request->input('field2');

        if ($request->input('api_key') != 'H5CQ3AETXBOW2CDZ') {
            header("Location: https://skiwatch.ru/");
            die();
        }


        try {
            $message = '';

            // Card ID
            if ($request->input('field1')) {
                $cardID = $request->input('field1');

                $registrations = [
                    '771CC7B00AE3' => [
                        'NAME' => 'Степан',
                        'LAST_NAME' => 'Кудряшов',
                    ],
                    '775CC7B00AE3' => [
                        'NAME' => 'Лидия',
                        'LAST_NAME' => 'Кудряшова',
                    ],
                    '72DCC7BB0AE3' => [
                        'NAME' => 'Дмитрий',
                        'LAST_NAME' => 'Крыгин',
                    ],
                    '76DCC7B00AE3' => [
                        'NAME' => 'Сергей',
                        'LAST_NAME' => 'Рисковец',
                    ],
                    '72DCC7A10AE3' => [
                        'NAME' => 'Любовь',
                        'LAST_NAME' => 'Рисковец',
                    ],
                    '769CC7B00AE3' => [
                        'NAME' => 'Сергей',
                        'LAST_NAME' => 'Чурилов',
                    ],
                    '731CC7BB0AE3' => [
                        'NAME' => 'Михаил (утерян)',
                        'LAST_NAME' => 'Бакунович',
                    ],
                    '731CC7A10AE3' => [
                        'NAME' => 'Михаил',
                        'LAST_NAME' => 'Бакунович',
                    ],
                    '7ADCC7A80AE3' => [
                        'NAME' => 'Вадим',
                        'LAST_NAME' => 'Семёнов',
                    ],
                    '7B1CC7A80AE3' => [
                        'NAME' => 'Айрат',
                        'LAST_NAME' => 'Якупов',
                    ],
                    '7A5CC7A80AE3' => [
                        'NAME' => 'Айрат (SL)',
                        'LAST_NAME' => 'Якупов',
                    ],
                    '7A9CC7A80AE3' => [
                        'NAME' => 'Егор',
                        'LAST_NAME' => 'Обухов',
                    ],
                    '765CC7960AE3' => [
                        'NAME' => 'Алексей',
                        'LAST_NAME' => 'Мирошниченко',
                    ],
                    '725CC7A10AE3' => [
                        'NAME' => 'Вероника',
                        'LAST_NAME' => 'Скрипачёва',
                    ],
                    '769CC7960AE3' => [
                        'NAME' => 'Елизавета',
                        'LAST_NAME' => 'Селезнёва',
                    ],
                    '729CC7A10AE3' => [
                        'NAME' => 'Александр',
                        'LAST_NAME' => 'Омельченко',
                    ],
                    '799CC78F0AE3' => [
                        'NAME' => 'Владимир',
                        'LAST_NAME' => 'Лазник',
                    ],
                    '000000' => [
                        'NAME' => 'Андрей',
                        'LAST_NAME' => 'Пац',
                    ],
                    '7A5CC78F0AE3' => [
                        'NAME' => 'Александр',
                        'LAST_NAME' => 'Чеботарёв',
                    ],
                ];

                $user = '';
                if (isset($registrations[$cardID])) {
                    $user .= $registrations[$cardID]['LAST_NAME'] . ' ' . $registrations[$cardID]['NAME'];
                } else {
                    $user .= "`" . $cardID . "`";
                }
                $user .= "\r\n";

                $message .= 'Участник: ' . $user;
            } else {
                $message .= 'Участник: без карты' . "\r\n";
            }

            if ($request->input('field2')) {
                $message .= 'Результат: ' . $request->input('field2');
            }

            $params = [
                'api_key' => $request->input('api_key'),
                'ACTION' => 'sendMessage',
                'DATA' => [
                    'parse_mode' => 'MARKDOWN',
                    //'chat_id' => '-4114294731', // Skiwatch.Test
                    'chat_id' => '-1001706366800', // Skiwatch.Develop
                    'text' => $message,
                ]
            ];

            //dump($params);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot5751029780:AAGitByhjGH8L84r9J-uGECJwHRO73w7cw8/sendMessage");
            curl_setopt($ch, CURLOPT_POST, 1);

            // In real life you should use something like:
            curl_setopt($ch, CURLOPT_POSTFIELDS,
                http_build_query($params['DATA']));

            // Receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $server_output = curl_exec($ch);

            curl_close($ch);

            // $ch = curl_init();
            //
            // curl_setopt($ch, CURLOPT_URL,"http://skiwatch.ru/api/proxy/telegram-bot/");
            // curl_setopt($ch, CURLOPT_POST, 1);
            //
            // // In real life you should use something like:
            // curl_setopt($ch, CURLOPT_POSTFIELDS,
            //     http_build_query($params));
            //
            // // Receive server response ...
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //
            // $server_output = curl_exec($ch);
            //
            // curl_close($ch);
            //
            // // Further processing ...
            // if ($server_output) {
            //     echo "ok";
            // } else {
            //     echo "error";
            //     var_dump($server_output);
            // }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
