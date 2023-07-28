<?php

namespace app\exception;
use \PDO;
use PDOException;
use think\Exception;

class PDOConnect
{

    private $pdo;

    /**
     * @throws Exception
     */
    public function __construct($config=[])
    {
        // 尝试连接到数据库
        try {
            $this->pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['db'],
                $config['username'],
                $config['password']
            );
            // 设置PDO错误模式为异常
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // 连接失败，输出错误信息
            throw new \think\Exception('连接数据库失败: ' . $e->getMessage(), 504);
        }
    }

    // 查询操作

    /**
     * @throws Exception
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \think\Exception('查询失败: ' . $e->getMessage(), 504);
        }
    }

    // 插入操作

    /**
     * @throws Exception
     */
    public function insert($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new \think\Exception('插入失败: ' . $e->getMessage(), 504);

        }
    }

    // 更新操作

    /**
     * @throws Exception
     */
    public function update($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \think\Exception('更新失败: ' . $e->getMessage(), 504);
        }
    }

    // 删除操作

    /**
     * @throws Exception
     */
    public function delete($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \think\Exception('删除失败: ' . $e->getMessage(), 504);
        }
    }


    public function closeConnect(){
        $this->pdo->closeCursor();
    }
}






