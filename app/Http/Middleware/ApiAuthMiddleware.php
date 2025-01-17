<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ngambil token nya
        $token = $request->header("Authorization");
        // autentikasi nya di set true dulu (jadi diangap berhasil dulu)
        $authenticate = true;
        if (!$token) {
            // kalo token nya engga ada, berarti anggap autentikasi nya false
            $authenticate = false;
        }
        // disini cari user dengan token yang dari client
        $user = User::where("token", $token)->first();
        if (!$user) {
            // kalo engga ada user nya, autentiikasinya false
            $authenticate = false;
        } else {
            // kalo ternyata ada usernya, berarti autentikasinya di loginkan
            //  disini udah ada  loginya berati
            Auth::login($user);
        }
        if ($authenticate) {
            return $next($request);
        } else {
            return response()->json([
                "errors" => [
                    "message" => "Unauthorized"
                ]
            ])->setStatusCode(401);
        }

    }
}
