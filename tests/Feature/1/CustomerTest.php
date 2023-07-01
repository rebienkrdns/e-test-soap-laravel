<?php

use App\Http\Soap\MainSoapServer;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    private $id;
    private $documento;
    private $nombres;
    private $correo;
    private $celular;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documento = '123456789';
        $this->nombres = 'Lorem Ipsum';
        $this->correo = 'example@example.com';
        $this->celular = '987654321';
    }

    /**
     * Create a customer for the first time.
     */
    public function test_create_ok_customer(): void
    {
        $response = $this->cast();

        $expected = [
            'success' => true,
            'cod_error' => '00',
            'message_error' => '',
            'data' => [
                'id' => $this->id,
                'documento' => $this->documento,
                'nombres' => $this->nombres,
                'correo' => $this->correo,
                'celular' => $this->celular
            ]
        ];

        $this->assertEquals($expected, $response);
    }

    /**
     * Create a customer with existing data.
     */
    public function test_create_existing_data_customer(): void
    {
        $response = $this->cast();

        $expected = [
            'success' => false,
            'cod_error' => '409',
            'message_error' => 'Ya existe un cliente registrado con ese correo electrónico',
            'data' => []
        ];

        $this->assertEquals($expected, $response);
    }

    /**
     * Create a customer with incomplete data.
     */
    public function test_create_incomplete_data_customer(): void
    {
        $response = (new MainSoapServer)->registroCliente('', $this->nombres, $this->correo, $this->celular);

        $expected = [
            'success' => false,
            'cod_error' => '400',
            'message_error' => 'Todos los campos son requeridos',
            'data' => []
        ];

        $this->assertEquals($expected, $response);
    }

    private function cast()
    {
        $data = (new MainSoapServer)->registroCliente($this->documento, $this->nombres, $this->correo, $this->celular);
        $this->id = $data['data']['id'] ?? null;
        return $data;
    }
}
