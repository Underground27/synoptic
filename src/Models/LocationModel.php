<?php
namespace Synoptic\Models;

class LocationModel
{
    protected $db;

    public function __construct(\Doctrine\DBAL\Connection $dbo)
    {
        $this->db = $dbo;
    }

	//��������� ���� ������� �� ��
    public function getAll()
    {
        return 'Get All Locations model method called!';
    }
	
	//��������� ����� �������
	public function get($id)
    {
        return 'Get location with id ' . $id . ' model method called!';
    }

	//���������� �������		
	public function add($data)
    {
        return 0;
    }

	//��������� �������		
	public function update($id, $data)
    {	
		return 0;
    }
	
	//�������� �������	
	public function delete($id)
    {
        return 0;
    }
}