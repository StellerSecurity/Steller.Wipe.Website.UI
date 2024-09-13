<?php

namespace App\Http\Controllers;

use App\Services\WipeService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

class LoginController extends Controller
{

    private WipeService $wipeService;

    public function __construct(WipeService $wipeService) {
        $this->wipeService = $wipeService;
    }

    public function detect_any_uppercase($string) {
        //Comparison operator. Returns true if lowercase changes string
        return strtolower($string) != $string;
    }


    public function detect_any_lowercase($string) {
        //true if uppercase changes string
        return strtoupper($string) != $string;
    }


    public function count_numbers($string) {
        return preg_match_all('/[0-9]/', $string);
    }

    public function count_symbols($string) {
        // You have to decide which symbols count
        // Regex /W is any non-letter, non-number: but this could be too broad
        // Better to list the ones that count
        // To write a regex here, you start with '', then inside that some square brackets [], then inside the square brackets is everything you want to include
        // Escape regex symbols to get their literal values - preg_quote helps facilitate that
        $regex = '/[' . preg_quote('!@£$%^&*-_+=?,.£¡@$½?') . ']/';
        return preg_match_all($regex, $string);
    }


    public function password_strength($password) {
        $strength = 0;
        $possible_points = 12;
        $length = strlen($password);

        if($this->detect_any_uppercase($password)) {
            $strength += 1;
        }

        if($this->detect_any_lowercase($password)) {
            $strength += 1;
        }

        // this adds points for numbers but limits the total possible to 2
        $strength += min($this->count_numbers($password), 2);
        // same again for symbols
        $strength += min($this->count_symbols($password), 2);

        $time_to_break_sec = 0;

        $recommend_length = false;

        if($length >= 11) {
            $recommend_length = true;
            $strength += 2;
            $strength += min(($length - 8) * 0.5, 4);
        }

        $sha1_password = sha1($password);
        $sha1_password = strtoupper($sha1_password);

        $hash = substr($sha1_password, 0, 5);

        $end_hash = substr($sha1_password, 5, strlen($sha1_password));
        $end_hash = strtoupper($end_hash);

        $contents = Storage::get('pwnedpasswords/' . $hash . '.txt');
        $compromised = explode($end_hash, $contents);

        $compromisedTimes = 0;
        if(count($compromised) > 1) {
            $string = str_replace(array("\n", "\r"), ' ', $compromised[1]);
            $string = explode(" ", $string);
            $compromisedTimes =  preg_replace('/[^0-9]+/', '', $string[0]);
        }


        $strength_percent = $strength / (float) $possible_points;
        $rating = floor($strength_percent * 10);

        if($rating == 0 or $rating == 1 or $rating == 2) {
            $time_to_break_sec = '2 seconds';
        }

        if($rating == 3) {
            $time_to_break_sec = '2 minutes';
        }

        if($rating == 4) {
            $time_to_break_sec = '1+ months';
        }

        if($rating == 5 or $rating == 6) {
            $time_to_break_sec = '2+ months';
        }

        if($rating == 7) {
            $time_to_break_sec = '4-8+ months';
        }

        if($rating == 8) {
            $time_to_break_sec = '3-6+ years';
        }

        if($rating == 9) {
            $time_to_break_sec = '10-30+ years';
        }

        if($rating >= 10) {
            $time_to_break_sec = '70+ years';
        }


        return [
            'rating' => $rating,
            'recommend_length' => $recommend_length,
            'has_lowercase' => $this->detect_any_lowercase($password),
            'has_uppercase' => $this->detect_any_uppercase($password),
            'has_numbers' => $this->count_numbers($password) > 0,
            'has_symbols' => $this->count_symbols($password) > 0,
            'compromised_times' => $compromisedTimes,
            'time_to_break' => $time_to_break_sec,
        ];

    }



    public function auth(Request $request) {

        $password = $_GET['password'];

        ?>


        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Password Strength</title>
            <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

            <style>
                #meter div {
                    height: 20px;
                    width: 20px;
                    margin: 0 1px 0 0;
                    padding: 0;
                    float: left;
                    background-color: #DDDDDD;
                }

                #meter div.rating-1, #meter div.rating-2 {
                    background-color: red;
                }

                #meter div.rating-3, #meter div.rating-4 {
                    background-color: orange;
                }

                #meter div.rating-5, #meter div.rating-6 {
                    background-color: yellow;
                }

                #meter div.rating-7, #meter div.rating-8 {
                    background-color: greenyellow;
                }

                #meter div.rating-9, #meter div.rating-10 {
                    background-color: green;
                }


            </style>
        </head>
    <body>

        <?php
        $rating = $this->password_strength($password);
        ?>

        Password: <strong><?php echo $password; ?></strong>
        <p>Your password strength is: <?php echo $rating['rating']; ?> out of 10.</p><br>
        Time it takes to crack your password: <strong><?php echo $rating['time_to_break']; ?></strong> (this is an estimation)
        <br><br>

        <div id="meter">
            <?php
            for($i=0; $i < 10; $i++) {
                echo "<div";
                if($rating > $i) {
                    echo " class=\"rating-{$rating['rating']}\"";
                }
                echo "></div>";
            }
            ?>
        </div><br><br>

        <?php
        if($rating['compromised_times'] > 0) {
            echo '<font color="red">Warning! The password has been compromised ' . $rating['compromised_times'] . ' times. We do not recommend using it.</font>';
        }
        ?>

        <?php


        exit;

        $data = array();

        if($request->isMethod('post')) {

            $login = null;
            if($request->input('method') == 0) {
                $username = $request->input('username');
                $password = $request->input('password');
                $login = $this->wipeService->auth($username, $password)->object();
            } else if($request->input('token') !== null) {
                $login = $this->wipeService->findbytoken($request->input('token'))->object();
            }


            if(!isset($login->auth_token)) {
                $data['error_message'] = "The login details you provided does not exist. Try again.";
            } else {
                session(['auth_token' => $login->auth_token]);
                return redirect('/dashboard');
            }

        }

        return view('auth.login', $data);

    }
}
