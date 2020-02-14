<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\User;

class AuthController extends Controller
{
  /**
   * Create a new AuthController instance.
   * 要求附带email和password（数据来源users表）
   *
   * @return void
   */
  public function __construct()
  {
    // 这里额外注意了：官方文档样例中只除外了『login』
    // 这样的结果是，token 只能在有效期以内进行刷新，过期无法刷新
    // 如果把 refresh 也放进去，token 即使过期但仍在刷新期以内也可刷新
    // 不过刷新一次作废
    $this->middleware('auth:api', ['except' => ['login', 'register', 'verify']]);
    // 另外关于上面的中间件，官方文档写的是『auth:api』
    // 但是我推荐用 『jwt.auth』，效果是一样的，但是有更加丰富的报错信息返回
  }

  /**
   * Get a JWT via given credentials.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function login()
  {
    $credentials = request(['email', 'password']);

    if (! $token = auth('api')->attempt($credentials)) {
      return response()->json(['error' => 'Unauthorized'], 401);
    }

    return $this->respondWithToken($token);
  }

  /**
   * Get the authenticated User.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function me()
  {
    return response()->json(auth('api')->user());
  }

  /**
   * Log the user out (Invalidate the token).
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function logout()
  {
    // can get bearer token only from header
//    $bearToken = request()->bearerToken();
//    echo 'bearToken: '.$bearToken.PHP_EOL;

    // server error with below
//    $jwtToken = JWTAuth::getToken();

    auth('api')->logout();
//
//
//
//    $token = request()->get('token');
//    JWTAuth::invalidate(JWTAuth::getToken());

    return response()->json(['message' => 'Successfully logged out']);
  }

  /**
   * Refresh a token.
   * 刷新token，如果开启黑名单，以前的token便会失效。
   * 值得注意的是用上面的getToken再获取一次Token并不算做刷新，两次获得的Token是并行的，即两个都可用。
   * @return \Illuminate\Http\JsonResponse
   */
  public function refresh()
  {
    return $this->respondWithToken(auth('api')->refresh());
  }

  /**
   * Get the token array structure.
   *
   * @param  string $token
   *
   * @return \Illuminate\Http\JsonResponse
   */
  protected function respondWithToken($token)
  {
    return response()->json([
      'access_token' => $token,
      'token_type' => 'bearer',
      'expires_in' => auth('api')->factory()->getTTL() * 60
    ]);
  }

  public function register(Request $request) {
    $newUser = $request->all();

    // check email
    if (!array_key_exists('email', $newUser)) {
      return response()->json([
        'status' => false,
        'message' => 'Email duplicated',
        'messageTag' => 'msg_email_duplicated'
      ]);
    }

    if (User::whereEmail($newUser['email'])->count() > 0) {
      return response()->json([
        'status' => false,
        'message' => 'Email already registered',
        'messageTag' => 'email_already_registered'
      ]);
    }

    $confirmationCode = str_random(30);
    $newUser['confirmation_code'] = $confirmationCode;

    User::create($newUser);

    // Send verification email
    Mail::send('email.verify', [
      'confirmationCode' => $confirmationCode
    ], function( $message) {
       $message
         ->to(\Input::get('email'), \Input::get('username'))
         ->subject('Verify your email');
    });
    return response()->json([
      'status' => true,
      'message' => 'Signed up successfully.',
      'messageTag' => 'msg_sign_up_success'
    ]);
  }
}