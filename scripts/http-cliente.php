<?php

// Cliente HTTP minimo con las cookies en memoria, para los scripts de testeo.
//
// POR QUE NO SE USA EL COOKIE JAR DE CURL: en PHP 8 el archivo del jar no se
// escribe hasta que el handle se destruye, y dentro de un script eso pasa al
// final. El servidor se recibe entonces sin la cookie de sesion y todo
// responde 403. Las cookies se leen de las cabeceras Set-Cookie y se mandan
// a mano en el header Cookie.

class ClienteHttp
{
    /** @var array<string, string> */
    private array $cookies = [];

    /**
     * @param  array<string, mixed>|null  $post
     * @return array{html: string, binario: string, codigo: int, destino: string, tipo: string}
     */
    public function pedir(string $url, ?array $post = null, bool $binario = false): array
    {
        $ch = curl_init($url);

        $opciones = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true,
        ];

        if ($this->cookies) {
            $cabecera = [];
            foreach ($this->cookies as $nombre => $valor) {
                $cabecera[] = $nombre . '=' . $valor;
            }
            $opciones[CURLOPT_HTTPHEADER] = ['Cookie: ' . implode('; ', $cabecera)];
        }

        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = $post;
        }

        curl_setopt_array($ch, $opciones);
        $respuesta = (string) curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $destino = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $tipo = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $tamano = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $cabeceras = substr($respuesta, 0, $tamano);
        $cuerpo = substr($respuesta, $tamano);

        if (preg_match_all('/^Set-Cookie:\s*([^=]+)=([^;]*)/mi', $cabeceras, $m)) {
            foreach ($m[1] as $i => $nombre) {
                $this->cookies[trim($nombre)] = trim($m[2][$i]);
            }
        }

        return [
            'html'    => $binario ? '' : $cuerpo,
            'binario' => $binario ? $cuerpo : '',
            'codigo'  => $codigo,
            'destino' => $destino,
            'tipo'    => $tipo,
        ];
    }

    /**
     * Token CSRF de una pagina.
     */
    public function token(string $url): string
    {
        $html = $this->pedir($url)['html'];
        preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);

        return $m[1] ?? '';
    }

    /**
     * Login. Devuelve el codigo HTTP de la respuesta.
     */
    public function login(string $base, string $email, string $password): int
    {
        $r = $this->pedir("$base/login", [
            '_token'   => $this->token("$base/login"),
            'email'    => $email,
            'password' => $password,
        ]);

        return $r['codigo'];
    }
}

/**
 * Contador de pasos ok/fallidos para los scripts de testeo.
 */
class Contador
{
    public int $ok = 0;
    public int $fallos = 0;

    public function paso(string $titulo, bool $bien, string $detalle = ''): void
    {
        if ($bien) {
            $this->ok++;
            echo "  [ok]    {$titulo}" . ($detalle ? "  ({$detalle})" : '') . "\n";
        } else {
            $this->fallos++;
            echo "  [FALLA] {$titulo}" . ($detalle ? "  ({$detalle})" : '') . "\n";
        }
    }

    public function resumen(): string
    {
        return "\n  {$this->ok} pasos ok, {$this->fallos} fallidos\n";
    }
}
