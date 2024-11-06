<section class="login">
    <div class="container-fluid">
        <h1>Login</h1>

        <form action="" class="form w-50">
            <div class="login_form">
                <div class="row pt-3 mb-2">
                    <div class="col mt-4">
                        <label for="email">Email</label>
                        <input class="form-control mt-2" type="text" name="email">
                    </div>
                </div>
                <div class="row">

                    <div class="col">
                        <label for="password">Password</label>
                        <input class="form-control mt-2" type="text" name="password">
                        <div class="text-end mt-2 mb-4 ">
                            <a class="" href="">Forgot Password?</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col d-flex flex-column py-3 gap-3">
                        <button class="button-primary">Register</button>

                        <button class="button-secondary">
                            <img src="{{asset('assets/svgs/microsift-icon.svg')}}" alt="">
                            <span class="ms-2">continue with Microsoft</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</section>
