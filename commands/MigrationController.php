<?php

namespace app\commands;

use app\models\BaseModel;
use app\models\BaseModelDB2 as BaseModel2;
use Yii;
use yii\console\ExitCode;

class MigrationController extends \yii\console\Controller
{

    /**
     * This command generate migration schema from $table1 to $table2.
     * @param string $table1
     * @param string $table2
     * @return int Exit code
     */
    public function actionGenerate($table1, $table2, $fileName = null)
    {
        try {
            $model1 = new BaseModel($table1);
            $model2 = new BaseModel2($table2);
            echo "Reading schema\n";
            $columns1 = $model1->tableSchema->columnNames;
            $columns2 = $model2->tableSchema->columnNames;
            $combined = [];

            foreach ($columns1 as $key) {
                $combined[$key] = in_array($key, $columns2) ? $key : '';
            }

            $schema = [];
            $schema['tables'] = [
                'from' => ['table' => $table1, 'columns' => $columns1],
                'to' => ['table' => $table2, 'columns' => $columns2],
            ];
            $schema['mapping'] = $combined;
            $schema['overwrites'] = [];
            $schema['defaults'] = [];
            $schema['id_mappings'] = [];

            $ok = $this->confirm("Do you want to generate migration schema to migrate data from `$table1` into `$table2`", false);

            if (!$ok) {
                echo "Permission denied, migration schema not generated.\n";
                return ExitCode::OK;
            }

            $fileName = $fileName ?: $table2;

            if (is_file("./migration-schema/$table2.json")) {
                $overwrite = $this->confirm("File exists $table2.json, Do you want to overwrite?");

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
            // echo $e->getTraceAsString() . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * This command reads migration schema from $table and migrate it.
     * @param string $table
     * @return int Exit code
     */
    public function actionApply($table, $previewData = false)
    {
        try {
            echo "Reading configuration\n";

            $config = file_get_contents("./migration-schema/$table.json");
            $config = json_decode($config, true);
            $table1 = $config['tables']['from']['table'];
            $table2 = $config['tables']['to']['table'];
            $mapping = $config['mapping'];
            $overwrites = $config['overwrites'];
            $defaults = $config['defaults'];

            $ok = $this->confirm("Do you want to migrate data from `$table1` into `$table2`", false);

            if (!$ok) {
                echo "Permission denied, migration cancelled.\n";
                return ExitCode::OK;
            }

            $limit = $this->prompt("What is limit of data to fetch?", ['default' => 1000]);
            $offset = $this->prompt("What is the offset of data? [0]", ['default' => 0]);

            echo "Preparing for migration.\n";
            $sql = "SELECT * FROM $table1 limit $limit offset $offset";
            echo "Executing $sql\n";

            $models = Yii::$app->db->createCommand($sql)->queryAll();
            Yii::$app->db2->createCommand("SET FOREIGN_KEY_CHECKS=0;")->execute();

            // $id_mappings = "Cancelled-8,Complete-7,Booked-6,Confirmed-9,Checked In-10";
            // $ids = explode(",", $id_mappings);
            // $id_mappings = [];
            // foreach ($ids as $id) {
            //     list($t_id, $id) = explode('-', $id);
            //     $id_mappings[$t_id] = $id;
            // }
            // file_put_contents('id-mappings.json', json_encode($id_mappings));
            // echo json_encode($id_mappings) . PHP_EOL;

            /**
             * here key defines existing and value defines the new value of the column.
             */
            $id_mappings = $config['id_mappings'];

            foreach ($models as $model) {
                $newModel = new BaseModel2($table2);
                $data = [];

                foreach ($mapping as $existingColumn => $newColumn) {
                    if (isset($newColumn) && $newColumn != '') {
                        /**
                         * Is there any ID mapping for the $existingColumn?
                         */
                        if (array_key_exists($existingColumn, $id_mappings)) {
                            $data[$newColumn] = $id_mappings[$existingColumn][$model[$existingColumn]];
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

                if ($previewData) {
                    print_r($data);
                    return ExitCode::OK;
                }

                /**
                 * Set the extracted attributes from $table1.
                 */
                $newModel->setAttributes($data, false);

                echo "Saving record #{$model['id']}.\n";

                if (!$newModel->save(false)) {
                    echo "Unable to save new rocord on #{$model['id']}";
                    return ExitCode::UNSPECIFIED_ERROR;
                }

                echo "Record #{$model['id']} saved.\n";
            }

            Yii::$app->db2->createCommand("SET FOREIGN_KEY_CHECKS=1;")->execute();

            echo "done.\n";

            return ExitCode::OK;
        } catch (\Exception $e) {
            echo $e->getMessage();
            // echo $e->getTraceAsString();
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * This command echoes SQL query to insert data into $table2 from $table1.
     * @param string $table1
     * * @param string $table2
     * @return int Exit code
     */
    public function actionQuery($table1, $table2)
    {
        try {
            $model1 = new BaseModel($table1);
            // $model2 = new BaseModel2($table2);
            echo "Reading schema\n";
            $columns1 = $model1->tableSchema->columnNames;
            // $columns2 = $model2->tableSchema->columnNames;
            $columns = implode(', ', $columns1);
            $sql = "INSERT INTO `squeeze-v2`.$table2(SELECT $columns FROM `squeeze-staging-b`.$table1)";
            echo $sql . PHP_EOL;

            return ExitCode::OK;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            // echo $e->getTraceAsString() . PHP_EOL;
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
