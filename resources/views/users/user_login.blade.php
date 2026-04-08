<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Cinema System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="flex w-full max-w-4xl bg-white rounded-lg shadow-lg overflow-hidden">
        
        <div class="hidden md:block md:w-1/2 bg-cover bg-center" 
             style="background-image: url('{{ asset('cinema_login_page_pic.png') }}')">
        </div>

        <div class="w-full p-8 md:w-1/2">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Explore the things you love.</h2>
            
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700">Email Address</label>
                    <input type="email" name="email_address" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">Continue</button>
            </form>

            <div class="mt-4 text-center">
                <a href="#" class="text-sm text-blue-500">Create new account</a>
            </div>
        </div>
    </div>
</body>
</html>