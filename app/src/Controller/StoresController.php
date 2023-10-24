<?php
declare(strict_types=1);

namespace App\Controller;

use App\Services\GetAddressDataService;
use Cake\Database\Expression\QueryExpression;
use Cake\View\JsonView;

/**
 * Stores Controller
 *
 * @property \App\Model\Table\StoresTable $Stores
 * @method \App\Model\Entity\Store[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class StoresController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    public function index(): void
    {
        $stores = $this->Stores->find()
            ->select([
                'Stores.id',
                'Stores.name',
                'Address.id',
                'Address.postal_code',
                'Address.state',
                'Address.city',
                'Address.sublocality',
                'Address.street',
                'Address.street_number',
                'Address.complement',
                'postal_code_masked' => new QueryExpression("CONCAT(SUBSTRING(Address.postal_code, 1, 5), '-', SUBSTRING(Address.postal_code, 6, 3))")
            ])
            ->join([
                'table' => 'addresses',
                'alias' => 'Address',
                'type' => 'LEFT',
                'conditions' => [
                    'Address.foreign_table' => 'stores',
                    'Address.foreign_id = Stores.id',
                ]
            ])
            ->all();

        $this->set('stores', $stores);
        $this->viewBuilder()->setOption('serialize', ['stores']);
    }

    public function view($id = null): void
    {
        $store = $this->Stores->find()
            ->select([
                'Stores.id',
                'Stores.name',
                'Address.id',
                'Address.postal_code',
                'Address.state',
                'Address.city',
                'Address.sublocality',
                'Address.street',
                'Address.street_number',
                'Address.complement',
                'postal_code_masked' => new QueryExpression("CONCAT(SUBSTRING(Address.postal_code, 1, 5), '-', SUBSTRING(Address.postal_code, 6, 3))")
            ])
            ->join([
                'table' => 'addresses',
                'alias' => 'Address',
                'type' => 'LEFT',
                'conditions' => [
                    'Address.foreign_table' => 'stores',
                    'Address.foreign_id = Stores.id',
                ]
            ])
            ->where(['Stores.id' => $id])
            ->first();

        $this->set('store', $store);
        $this->viewBuilder()->setOption('serialize', ['store']);
    }

    public function add(): void
    {
        $this->request->allowMethod(['post', 'put']);
        $data = $this->request->getData();

        unset($data['id']);
        unset($data['address']['id']);
        unset($data['address']['foreign_table']);
        unset($data['address']['foreign_id']);
        unset($data['address']['state']);
        unset($data['address']['city']);
        unset($data['address']['sublocality']);
        unset($data['address']['street']);

        $apiData = GetAddressDataService::handle($data['address']['postal_code']);

        if (!$apiData) {
            $this->set([
                'message' => 'CEP não encontrado'
            ]);
            $this->viewBuilder()->setOption('serialize', ['message']);
            $this->response = $this->response->withStatus(422);
            return;
        }
        $data['address']['state'] = $apiData['uf'];
        $data['address']['city'] = $apiData['localidade'];
        $data['address']['sublocality'] = $apiData['bairro'];
        $data['address']['street'] = $apiData['logradouro'];

        $store = $this->Stores->find()
            ->where(['name' => $data['name']])
            ->first();

        if ($store) {
            $this->set([
                'message' => 'Nome em uso'
            ]);
            $this->viewBuilder()->setOption('serialize', ['message']);
            $this->response = $this->response->withStatus(422);
            return;
        }
        $store = $this->Stores->newEntity($data);
        if ($this->Stores->save($store)) {
            $addressData = $data['address'];
            $addressData['foreign_table'] = 'stores';
            $addressData['foreign_id'] = $store->id;
            $address = $this->Stores->Addresses->newEntity($addressData);
            if ($this->Stores->Addresses->save($address)) {
                $message = 'Saved';
            } else {
                $this->response = $this->response->withStatus(422);
                $message = 'Error';
            }
        } else {
            $message = 'Error';
        }

        $this->set([
            'message' => $message,
            'store' => $store
        ]);

        $this->viewBuilder()->setOption('serialize', ['store', 'message']);
    }

    public function edit($id = null): void
    {
        $this->request->allowMethod(['patch', 'post', 'put']);
        $store = $this->Stores->get($id);
        $data = $this->request->getData();

        unset($data['id']);
        unset($data['address']['id']);
        unset($data['address']['foreign_table']);
        unset($data['address']['foreign_id']);
        unset($data['address']['state']);
        unset($data['address']['city']);
        unset($data['address']['sublocality']);
        unset($data['address']['street']);

        $apiData = GetAddressDataService::handle($data['address']['postal_code']);
        if (!$apiData) {
            $this->set([
                'message' => 'CEP não encontrado'
            ]);
            $this->viewBuilder()->setOption('serialize', ['message']);
            $this->response = $this->response->withStatus(422);
            return;
        }
        // cep.la não funcionou, não achei nem nada sobre
        $data['address']['state'] = $apiData['uf'];
        $data['address']['city'] = $apiData['localidade'];
        $data['address']['sublocality'] = $apiData['bairro'];
        $data['address']['street'] = $apiData['logradouro'];

        $anyStore = $this->Stores->find()
            ->where(['name' => $data['name']])
            ->first();

        if ($anyStore && $anyStore->id != $id) {
            $this->set([
                'message' => 'Nome em uso'
            ]);
            $this->viewBuilder()->setOption('serialize', ['message']);
            $this->response = $this->response->withStatus(422);
            return;
        }
        $store = $this->Stores->patchEntity($store, $data);
        if ($this->Stores->save($store)) {
            $this->Stores->Addresses->deleteAll([
                'foreign_table' => 'stores',
                'foreign_id' => $store->id
            ]);
            $addressData = $data['address'];
            $addressData['foreign_table'] = 'stores';
            $addressData['foreign_id'] = $store->id;

            $address = $this->Stores->Addresses->newEntity($addressData);
            if ($this->Stores->Addresses->save($address)) {
                $message = 'Saved';
            } else {
                $this->response = $this->response->withStatus(422);
                $message = 'Error';
            }
        } else {
            $this->response = $this->response->withStatus(422);
            $message = 'Error';
        }

        $this->set([
            'message' => $message,
            'store' => $store,
        ]);

        $this->viewBuilder()->setOption('serialize', ['store', 'message']);
    }

    public function delete($id = null): void
    {
        $this->request->allowMethod(['delete']);
        $store = $this->Stores->get($id);
        $message = 'Deleted';
        if (!$this->Stores->delete($store)) {
            $message = 'Error';
        }

        $this->set([
            'message' => $message,
        ]);

        $this->viewBuilder()->setOption('serialize', ['message']);
    }
}
