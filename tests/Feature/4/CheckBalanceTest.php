<?php

use App\Http\Soap\MainSoapServer;
use Tests\TestCase;

class CheckBalanceTest extends TestCase
{
    private $customerId;
    private $documento;
    private $nombres;
    private $correo;
    private $celular;
    private $valorAAgregar;
    private $valorAPagar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documento = '123456789';
        $this->nombres = 'Lorem Ipsum';
        $this->correo = 'example@example.com';
        $this->celular = '987654321';
        $this->valorAAgregar = 100000;
        $this->valorAPagar = 23476.34;
    }

    /**
     * Check balance.
     */
    public function test_ok_check_balance(): void
    {
        $response = $this->cast();

        $expected = [
            'success' => true,
            'cod_error' => '00',
            'message_error' => '',
            'data' => [
                'cliente' => [
                    'id' => $this->customerId,
                    'documento' => $this->documento,
                    'nombres' => $this->nombres,
                    'correo' => $this->correo,
                    'celular' => $this->celular
                ],
                'balance_billetera' => $this->valorAAgregar - $this->valorAPagar
            ]
        ];

        $this->assertEquals($expected, $response);
    }

    /**
     * Check wrong balance.
     */
    public function test_wrong_check_balance(): void
    {
        $response = $this->cast();

        $expected = [
            'success' => true,
            'cod_error' => '00',
            'message_error' => '',
            'data' => [
                'cliente' => [
                    'id' => $this->customerId,
                    'documento' => $this->documento,
                    'nombres' => $this->nombres,
                    'correo' => $this->correo,
                    'celular' => $this->celular
                ],
                'balance_billetera' => $this->valorAAgregar
            ]
        ];

        $this->assertNotEquals($expected, $response);
    }

    private function cast()
    {
        $data = (new MainSoapServer)->consultarSaldo($this->documento, $this->celular);
        $this->customerId = $data['data']['cliente']['id'] ?? null;
        return $data;
    }
}
