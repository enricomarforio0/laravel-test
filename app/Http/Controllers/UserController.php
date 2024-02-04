<?php

namespace App\Http\Controllers;

use App\Models\UserVander;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Str;

class UserController extends Controller
{

    private $regexEmail = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
    private $regexPw='/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/';

    private $encryption_iv = '1234567891011121';
    private $encryption_key = "vandertest";

    private $ciphering = "aes-256-cbc";

    private $iv_length = 16;
    private $options = 0;
 
    /**
     * Display a listing of the resource.
     */

     private function encrypt($string) {
        return openssl_encrypt($string, $this->ciphering,
        $this->encryption_key, $this->options, $this->encryption_iv);
     }

     private function decrypt($string) {
        return openssl_decrypt ($string, $this->ciphering, 
        $this->encryption_key, $this->options, $this->encryption_iv);
     }

     
    public function index()
    {
        $users = DB::table("users")
            ->orderBy("nome")
            ->join("login", "users.id", "=", "login.userid")
            ->get();
        foreach ($users as $user) {
            $user->Email = $this -> decrypt($user->Email);
        }
        return view("app", ["users" => $users])->with(request()->input("page"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("user.create");
    }

    private function validateRegister(Request $request){
        $invalidFields = [];
        
        if($request->query("name") == '') {
            array_push($invalidFields, 'Name');
        }

        if($request->query("surname") == '') {
            array_push($invalidFields, 'Surname');
        }

        if($request->query("email") == '' || !preg_match($this->regexEmail, $request->query("email"))) {
            array_push($invalidFields, 'Email');
        }

        if($request->query("password") == '' || !preg_match($this->regexPw, $request->query("password"))) {
            array_push($invalidFields, 'Password');
        }

        if($request->query("confirmpassword") == '' || $request->query("confirmpassword") != $request->query("password")) {
            array_push($invalidFields, 'Confirm Password');
        }

        if(count($invalidFields) > 0) {
           throw new Exception("I seguenti campi non sono validi: ".implode(" ",$invalidFields));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $id = (string) Str::orderedUuid();
        $loginId = (string) Str::orderedUuid();
        
        try {
            $this -> validateRegister($request);
        } catch (Exception $ex) {
           return redirect()
           ->route("user.create")
           ->withErrors($ex->getMessage());
        }

        //controllo che non esista login

        try {
            //controllo inserimento
            $loginValid = DB::table("login")
                ->where("Email", $this->encrypt($request->query("email")))
                ->get();
            if($loginValid ->isNotEmpty()) {
                return redirect()
           ->route("user.create")
           ->withErrors('Utente già registrato');
            }
        } catch (e) {
            return redirect()
           ->route("user.create")
           ->withErrors('Errore durante la registrazione');
        }

        //inserisco utente
        try {
            DB::table("users")->insert([
                [
                    "Id" => $id,
                    "Nome" => $request->query("name"),
                    "Cognome" => $request->query("surname"),
                ],
            ]);
        } catch (e) {
            return redirect()
           ->route("user.create")
           ->withErrors('Errore durante la registrazione');
        }

        try {
            //controllo inserimento
            $userInserted = DB::table("users")
                ->where("id", $id)
                ->get();
        } catch (e) {
            return redirect()
           ->route("user.create")
           ->withErrors('Errore durante la registrazione');
        }

        //se inserito procedo a inserire i login
        $checkUser = $userInserted->isNotEmpty();
        try {
            if ($checkUser) {
                DB::table("login")->insert([
                    [
                        "Id" => $loginId,
                        "Email" => $this->encrypt($request->query("email")),
                        "UserId" => $id,
                        "Password" => md5($request->query("password")),
                    ],
                ]);
            }
        } catch (e) {
            DB::table("users")
                ->where("id", $id)
                ->delete();
                return redirect()
           ->route("user.create")
           ->withErrors('Errore durante la registrazione');
        }

        try {
            //controllo inserimento
            $loginInserted = DB::table("login")
                ->where("id", $loginId)
                ->get();
        } catch (e) {
            return redirect()
           ->route("user.create")
           ->withErrors('Errore durante la registrazione');
        }
        //se non inserito procedo a fare rollback
        $checkLogin = $loginInserted->isEmpty();
        try {
            if ($checkLogin) {
                DB::table("users")
                    ->where("id", $id)
                    ->delete();
            }
        } catch (e) {
            return redirect()
           ->route("user.create")
           ->withErrors('Errore durante il rollback');
        }

            $response = new \Illuminate\Http\Response(redirect() 
            ->route("user.index") 
            ->with("success", "Utente Registrato"));

            $response->withCookie(cookie('userId', $id));
            return $response;
        }

    /**
     * Display the specified resource.
     */
    public function show(UserVander $userVander)
    {
    }

    public function logout(Request $request)
    {


        $response = new \Illuminate\Http\Response(redirect('/login') ->with("success", "Utente Sloggato"));

            $response->withCookie(cookie('userId', null));
            return $response;
        ;
    }

    public function find(Request $request)
    {
        $checkUser = null;
        try {
            $checkUser = DB::table("login")
                ->where("Email", $this->encrypt($request->query("email")))
                ->where("Password", md5($request->query("password")))
                ->get();
        } catch (e) {
            return redirect()
           ->route("user.create")
           ->withErrors('Errore durante la registrazione');
        }

        if ($checkUser != null && $checkUser->isNotEmpty()) {
            $response = new \Illuminate\Http\Response(redirect('/user') ->with("success", "Utente Loggato"));

            $response->withCookie(cookie('userId', $checkUser[0] -> Id));
            return $response;
                
        } else {
            return redirect('/login')
            ->withErrors('Email e password non corrette');
                
        }
    }

    public function validateAndRoute() {
        $checkUser = null;
        try {
            $checkUser = DB::table("users")
                ->where("Id", $_COOKIE['userId'])
                ->get();

        return $checkUser -> isNotEmpty() && $_COOKIE['userId'] == $checkUser[0] -> Id ? 
        redirect() ->route("user.index") : 
        redirect('/login');

        } catch (e) {
            return redirect('/login')
           ->withErrors('Errore durante la validazione');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserVander $userVander)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserVander $userVander)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserVander $userVander)
    {
        //
    }


}
