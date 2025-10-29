@extends('layouts.app')

@section('content')
<div class="limiter">
    <div class="container-login100">
        <div class="wrap-login100">
            <form method="POST"  action="{{ route('password.email') }}"  class="login100-form validate-form" onsubmit='show()'>
                @csrf
             
                <span class="login100-form-title p-b-43">
                    {{ config('app.name', 'Laravel') }}
                </span>
                <span class="login100-form-title p-b-43">
                    <h6>{{ __('Reset Password') }}</h6>
                </span>
                
                @if (session('status'))
                <div class="form-group alert alert-success alert-dismissable">
                    <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                    <strong>{{ session('status') }}</strong>
                </div>
                @endif
                @if($errors->any())
                    <div class="form-group alert alert-danger alert-dismissable">
                        <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                        <strong>{{$errors->first()}}</strong>
                    </div>
                @endif
                
                <div class="wrap-input100 " data-validate = "Valid email is required: ex@abc.xyz">
                    <input  type="email" class="input100" name="email" value="" required >
                    <span class="focus-input100"></span>
                    <span class="label-input100">Email</span>
                </div>

                <div class="container-login100-form-btn">
                    <button class="login100-form-btn" type='submit'>
                        Reset Password
                    </button>
                </div>
                <div class="flex-sb-m w-full p-t-3 p-b-32">

                    <div>
                        <a href="{{ route('login') }}" class="txt1">
                            Back to login
                        </a>
                    </div>
                </div>
        

                
                
            </form>

            <div class="login100-more" style="background-image: url('../images/images.jpg');">
            </div>
        </div>
    </div>
</div>
@endsection
