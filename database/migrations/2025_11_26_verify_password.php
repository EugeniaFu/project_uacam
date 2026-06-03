<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration {
    public function up() {
        $user = User::where('email', 'juan@uac.edu')->first();
        if($user) {
            echo "\n=== Usuario encontrado ===\n";
            echo "Email: " . $user->email . "\n";
            echo "Nombre: " . $user->name . "\n";
            echo "Hash: " . $user->password . "\n";
            echo "Activo: " . $user->active . "\n";
            
            // Probar contraseña
            if(Hash::check('123456', $user->password)) {
                echo "Contraseña '123456': CORRECTA\n";
            } else {
                echo "Contraseña '123456': INCORRECTA\n";
                // Rehashear
                $user->password = Hash::make('123456');
                $user->save();
                echo "Contraseña rehusheada\n";
            }
        } else {
            echo "Usuario no encontrado\n";
        }
    }
    
    public function down() {}
};
