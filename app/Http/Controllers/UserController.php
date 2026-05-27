<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{

    public function the_names()
    {

        $names = ["ahmed", "ali", "rawan", "fatima"];
        $capitalized = array_map('strtoupper', $names);
        return response()->json($capitalized);
    }




    public function checker(Request $request)
    {
        $json = file_get_contents(resource_path('views/users.json'));
        $users = json_decode($json, true);

        $email = $request->query('email');
        $phone = $request->query('phone');

        foreach ($users as $user) {
            if (($email && $user['email'] === $email) || ($phone && $user['phone'] === $phone)) {
                return response()->json(['message' => 'Success: User in the house!']);
            }
        }

        return response()->json(['message' => 'faild: User is not in the house!']);
    }





    // public function index()
    // {

    //     $users = [
    //         ['id' => 1, 'name' => "ahmad"],
    //         ['id' => 2, 'name' => "ali"],
    //         ['id' => 3, 'name' => "sara"],
    //     ];
    //     // foreach ($users as $user) {
    //     //     echo "User ID: " . $user['id'] . ", Name: " . $user['name'] . "\n";
    //     // }

    //     return response()->json(["name" => "ahmad"]);
    // }



    // public function checkuser($id)
    // {
    //     if ($id > 10) {
    //         return response()->json([' message' => '  access  denied ']);
    //     } else {
    //         return response()->json([' message' => '  access  accepted ']);
    //     }

    //     return response()->json($id);
    // }



    // public function the_names()
    // {
    //     $names = ["ahmed", "ali", "rawan", "fatima"];
    //     $capitalized = [];

    //     foreach ($names as $name) {
    //         $upperName = '';

    //         for ($i = 0; $i < strlen($name); $i++) {
    //             $char = $name[$i];
    //             $ascii = ord($char);


    //             if ($ascii >= 97 && $ascii <= 122) {
    //                 $upperName .= chr($ascii - 32);
    //             } else {
    //                 $upperName .= $char;
    //             }
    //         }
    //         $capitalized[] = $upperName;
    //     }

    //     return response()->json($capitalized);
    // }
}
