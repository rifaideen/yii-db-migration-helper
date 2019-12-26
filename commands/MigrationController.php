<?php

namespace app\commands;

use app\models\BaseModel;
use app\models\BaseModelDB2 as BaseModel2;
use Yii;
use yii\console\ExitCode;
use yii\helpers\FileHelper;

ini_set('memory_limit', '-1');
ini_set('max_execution_Time', '72000');
set_time_limit(72000);

class MigrationController extends \yii\console\Controller {
    /**
     * This command reads migration schema from $table and migrate it.
     * @param string $table
     * @return int Exit code
     */
    public function actionApply($table, $previewData = false, $isAutomation = false) {
        echo "Reading configuration\n";

        $config = file_get_contents($isAutomation ? $table : "./migration-schema/$table.json");
        $config = json_decode($config, true);
        $table1 = $config['from'];
        $tables = $config['to'];
        $viaQuery = [];
        $viaIndividual = [];

        /**
         * separate individual record migration and bulk migration via sql query.
         */
        foreach ($tables as $table_config) {
            if (isset($table_config['query'])) {
                $viaQuery[] = $table_config;
            } else {
                $viaIndividual[] = $table_config;
            }
        }

        if (!$isAutomation) {
            $ok = $this->confirm("Do you want to migrate data from `$table1`?", false);

            if (!$ok) {
                echo "Permission denied, migration cancelled.\n";
                return ExitCode::OK;
            }
        }

        try {
            /**
             * handle transaction manually this action is not automated.
             */
            if (!$isAutomation) {
                $transaction = Yii::$app->db2->beginTransaction();
            }

            $inserted = ExitCode::OK;

            if (!empty($viaIndividual)) {
                $inserted = $this->insertIndividualRecords($isAutomation, $table1, $viaIndividual, $previewData);

                if ($inserted !== ExitCode::OK) {
                    throw new \Exception('unable to process migration');
                }
            }

            if (!empty($viaQuery)) {

                foreach ($viaQuery as $table_config) {
                    $sql = $table_config['query'];

                    if (!$isAutomation) {
                        echo "Query to be executed:\n\n";
                        echo "$sql\n\n";

                        $confirm = $this->confirm("Pls review the above query and confirm, If it's okay.");

                        if (!$confirm) {
                            echo "Permission denied, action cancelled.\n";
                            return ExitCode::OK;
                        }
                    }

                    echo "Executing query: $sql\n";

                    Yii::$app->db2->createCommand("SET FOREIGN_KEY_CHECKS=0;")->execute();
                    Yii::$app->db2->createCommand($sql)->execute();
                    Yii::$app->db2->createCommand("SET FOREIGN_KEY_CHECKS=1;")->execute();
                }
            }

            /**
             * commit transaction manually when this action is not automated.
             */
            if (!$isAutomation) {
                $transaction->commit();
            }

            echo "done.\n";
        } catch (\Exception $e) {
            /**
             * rollback transaction manually when this action is not automated, otherwise throw the error.
             */
            if (!$isAutomation) {
                $transaction->rollBack();
            } else {
                throw $e;
            }

            echo $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Automate migration process.
     */
    public function actionAutomate() {
        try {
            $transaction = Yii::$app->db2->beginTransaction();
            $files = FileHelper::findFiles('./migration-schema');

            foreach ($files as $schema) {
                $config = file_get_contents($schema);
                $config = json_decode($config, true);
                echo "Executing $schema\n\n";

                if (isset($config['seeds'])) {
                    $this->actionSeed($schema, true);
                } else {
                    $this->actionApply($schema, false, true);
                }
                
                echo "done with $schema\n\n";
            }

            $transaction->commit();
            echo "migration done.\n";
        } catch (\Exception $e) {
            $transaction->rollBack();
            echo $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * This command generate migration schema config from $table1 to $table2. i.e, migration/generate-config <from>  
     * @param string $table1
     * @param string $table2
     * * @param string $fileName optional filename
     * @return int Exit code
     */
    public function actionGenerateConfig($table1, $tables, $fileName = null) {
        try {
            $model1 = new BaseModel($table1);
            echo "Reading schema\n";
            $columns1 = $model1->tableSchema->columnNames;

            $schema = [];
            $schema['from'] = $table1;
            $schema['to'] = [];

            $tables = explode(',', trim($tables));
            $tables = array_filter($tables);

            foreach ($tables as $table2) {
                $model2 = new BaseModel2($table2);
                $columns2 = $model2->tableSchema->columnNames;
                $combined = [];

                foreach ($columns1 as $key) {
                    $combined[$key] = in_array($key, $columns2) ? $key : '';
                }

                $schema['to'][] = [
                    'table' => $table2,
                    'mapping' => $combined,
                    'overwrites' => [],
                    'defaults' => [],
                    'id_mappings' => [],
                    'query' => $this->generateQuery($table1, $table2)
                ];
            }

            $ok = $this->confirm("Do you want to generate migration schema for `$table1`?", false);

            if (!$ok) {
                echo "Permission denied, migration schema not generated.\n";
                return ExitCode::OK;
            }

            $fileName = $fileName ?: $table1;

            if (is_file("./migration-schema/$fileName.json")) {
                $overwrite = $this->confirm("File exists $table1.json, Do you want to overwrite?");

                if (!$overwrite) {
                    $fileName = $this->prompt("Enter file name:");
                }
            }

            echo "Generating migration schema\n";
            file_put_contents("./migration-schema/$fileName.json", json_encode($schema));
            echo "done.\n";

            return ExitCode::OK;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Generate DB seed from `$from` into `$to`, `$fromV1DB` - whether seed is from v1 DB, defaults to 1.
     *
     * @param string $from
     * @param string $to
     * @param integer $fromV1DB
     * @return void
     */
    public function actionGenerateSeed($from, $to, $fromV1DB = 1) {
        if ($fromV1DB) {
            $model = new BaseModel($from);
        } else {
            $model = new BaseModel2($from);
        }

        $records = $model::find()->asArray()->all();
        $schema = [];
        $schema['table'] = $to;
        $schema['seeds'] = $records;

        echo "Generating migration schema\n";
        file_put_contents("./migration-schema/$to-seed.json", json_encode($schema));
        echo "done.\n";

        return ExitCode::OK;
    }

    /**
     * Helper command to manipulate id mappings.
     * @param string $table1
     * @param string $table2
     * @return int Exit code
     */
    public function actionMapping() {
        $id_mappings = "726420-2,726419-21,870098-22,874768-26,874771-29,870115-30,877240-31,878733-32,893246-40,893248-41,893247-42,893249-43,894052-44,894057-45,894060-46,894044-47,894047-48,894054-49,894049-50,894063-51,894061-52,894066-53,894058-54,894048-55,894045-56,894053-57,894059-58,894099-59,894098-60,756597-61,894549-62,899544-63,903437-64,903436-65,903506-66,906069-67,906084-68,908449-69,909402-70,916756-71,916824-72,921589-73,921595-74,924314-75,926324-76,926349-77,926714-78,927420-79,927394-80,929737-81,935988-82,935992-83,938937-84,940618-85,940620-86,943029-87,945736-88,945735-89,946422-90,946435-91,950332-92,952235-93,965739-94,965885-95,984949-96";
        $ids = explode(",", $id_mappings);
        $id_mappings = [];

        foreach ($ids as $id) {
            list($t_id, $id) = explode('-', $id);
            $id_mappings[$t_id] = $id;
        }

        file_put_contents('id-mappings.json', json_encode($id_mappings));
    }

    /**
     * Prints executable SQL query to insert data into $table2 from $table1.
     * @param string $table1
     * @param string $table2
     * @return int Exit code
     */
    public function actionPrintQuery($table1, $table2) {
        try {
            $sql = $this->generateQuery($table1, $table2);

            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "$sql\n";
            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            echo PHP_EOL;

            return ExitCode::OK;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Seed `$table`
     *
     * @param string $table
     * @param boolean $isAutomation
     */
    public function actionSeed($table, $isAutomation = false) {
        echo "Reading configuration\n";

        try {
            $config = file_get_contents($isAutomation ? $table : "./migration-schema/$table-seed.json");
            $config = json_decode($config, true);
            $table = $config['table'];

            if (!$isAutomation) {
                $transaction = Yii::$app->db2->beginTransaction();
            }

            Yii::$app->db2->createCommand("SET FOREIGN_KEY_CHECKS=0;")->execute();

            foreach($config['seeds'] as $data) {
                $newModel = new BaseModel2($table);
                /**
                 * Set the extracted attributes from $table1.
                 */
                $newModel->setAttributes($data, false);

                if (!$isAutomation) {
                    echo "Seeding record #{$data['id']}.\n";
                }

                if (!$newModel->save(false)) {
                    echo "Unable to seed new rocord on #{$data['id']}";
                    return ExitCode::UNSPECIFIED_ERROR;
                }

                if (!$isAutomation) {
                    echo "Record #{$data['id']} saved.\n";
                }
            }

            Yii::$app->db2->createCommand("SET FOREIGN_KEY_CHECKS=1;")->execute();

            /**
             * commit transaction manually when this action is not automated.
             */
            if (!$isAutomation) {
                $transaction->commit();
            }

            echo "done.\n";
        } catch(\Exception $e) {
            /**
             * rollback transaction manually when this action is not automated, otherwise throw the error.
             */
            if (!$isAutomation) {
                $transaction->rollBack();
            } else {
                throw $e;
            }

            echo $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Generate SQL insert query from $table1 into $table2
     *
     * @param String $table1
     * @param String $table2
     * @return String
     */
    private function generateQuery($table1, $table2) {
        $model1 = new BaseModel($table1);
        $columns1 = $model1->tableSchema->columnNames;

        foreach ($columns1 as $key => $value) {
            $columns1[$key] = "t.$value";
        }

        $columns = implode(', ', $columns1);
        $sql = "INSERT INTO `squeeze-v2`.$table2(SELECT $columns FROM `squeeze`.$table1 as t);";

        return $sql;
    }

    /**
     * Select all records and migrate record one by one.
     * @return ExitCode::OK
     */
    private function insertIndividualRecords($isAutomation, $table1, $tables, $previewData) {
        $sql = "SELECT count(*) FROM $table1";
        $count = Yii::$app->db->createCommand($sql)->queryScalar();

        if (!$isAutomation) {
            $limit = $this->prompt("What is limit of data to fetch?", ['default' => $count]);
        } else {
            $limit = 5000;
        }


        $remainder = $count % $limit;
        $pages = intdiv($count, $limit);
        $chunks = $pages + 1;

        echo "The data migration has been split into $chunks chunk(s), based on the limit you've choosen.\n";

        for ($i = 0; $i <= $pages; $i++) {
            $offset = $i * $limit;

            if ($i == $pages) {
                $limit = $remainder;
            }

            echo "Executing Chunk #" . ($i + 1) . "\n";
            $sql = "SELECT * FROM $table1 limit $limit offset $offset";
            echo "Query executed: $sql\n";
            Yii::$app->db2->createCommand("SET FOREIGN_KEY_CHECKS=0;")->execute();
            $models = Yii::$app->db->createCommand($sql)->queryAll();

            foreach ($models as $model) {
                /**
                 * insert individual record into various tables.
                 */
                foreach ($tables as $table_config) {
                    $table = $table_config['table'];
                    $mapping = $table_config['mapping'];
                    $overwrites = $table_config['overwrites'];
                    $defaults = $table_config['defaults'];
                    /**
                     * here key defines existing and value defines the new value of the column.
                     */
                    $id_mappings = $table_config['id_mappings'];
                    $fromTable = $table1;
                    $params = compact(
                        'table',
                        'mapping',
                        'overwrites',
                        'defaults',
                        'id_mappings',
                        'previewData',
                        'isAutomation',
                        'fromTable'
                    );

                    $this->insert($model, $table, $params);
                }
            }

            Yii::$app->db2->createCommand("SET FOREIGN_KEY_CHECKS=1;")->execute();
        }

        return ExitCode::OK;
    }

    /**
     * Insert $models into $table with respect to $config
     *
     * @param String $model
     * @param String $table
     * @param Array $config
     * @return void
     */
    private function insert($model, $table, $config) {
        extract($config);
        $data = [];

        foreach ($mapping as $existingColumn => $newColumn) {
            if (isset($newColumn) && $newColumn != '') {
                /**
                 * Is there any ID mapping for the $existingColumn?
                 */
                if (array_key_exists($existingColumn, $id_mappings)) {
                    $data[$newColumn] = isset($id_mappings[$existingColumn][$model[$existingColumn]]) ? $id_mappings[$existingColumn][$model[$existingColumn]] : 0;
                } else {
                    /**
                     * Is there any overwrite value for $existingColumn?
                     */
                    $data[$newColumn] = array_key_exists($existingColumn, $overwrites) ? $overwrites[$existingColumn] : $model[$existingColumn];
                }
            }
        }

        /**
         * Apply any default value required in the table it is migrating.
         */
        foreach ($defaults as $dk => $dv) {
            $data[$dk] = $dv;
        }

        $isDataHandled = false;

        /**
         * handle any customization here.
         */

        if ($previewData) {
            print_r($data);
            return ExitCode::OK;
        }

        if (!$isDataHandled) {
            $newModel = new BaseModel2($table);
            /**
             * Set the extracted attributes from $table1.
             */
            $newModel->setAttributes($data, false);

            if (!$isAutomation) {
                echo "Saving record #{$model['id']}.\n";
            }

            if (!$newModel->save(false)) {
                echo "Unable to save new rocord on #{$model['id']}";
                return ExitCode::UNSPECIFIED_ERROR;
            }

            if (!$isAutomation) {
                echo "Record #{$model['id']} saved.\n";
            }
        }
    }
}
