<?php
namespace Synoptic\Models;

class LocationModel
{
    protected $db;

    public function __construct(\Doctrine\DBAL\Connection $dbo)
    {
        $this->db = $dbo;
    }

	//Получение всех локаций из БД
    public function getAll()
    {
        return 'Get All Locations model method called!';
    }
	
	//Получение одной локации
	public function get($id)
    {
        return 'Get location with id ' . $id . ' model method called!';
    }

	//Добавление локации		
	public function add($data)
    {
        return 0;
    }

	//Изменение локации		
	public function update($id, $data)
    {	
		return 0;
    }
	
	//Удаление локации	
	public function delete($id)
    {
        return 0;
    }
}