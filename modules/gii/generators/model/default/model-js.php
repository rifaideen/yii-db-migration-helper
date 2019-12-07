const Joi = require('@hapi/joi');
const Api = require('./api');

/**
 * private fields should not be exposed to public.
 */
const privateFields = [
<?php
foreach ($privateFields as $privateField) {
    $privateField = trim($privateField);
    echo "  '$privateField',\n";
}
?>
];
<?php
$nonFilterrJS = "";
foreach ($nonFilters as $nonFilter) {
    $nonFilter = trim($nonFilter);
    $nonFilterrJS .= "'$nonFilter', ";
}
?>

module.exports = class <?= $className ?> extends Api {
  /**
   * table name for this API endpoint.
   */
  static get tableName() {
    return '<?= $generator->generateTableName($tableName) ?>';
  }

  /**
   * Fields listed here won't be exposed in API response.
   */
  static entityFilteringScope() {
    return {
      admin: privateFields,
      user: privateFields,
      guest: privateFields,
    };
  }

  /**
   * Any valid first level DB column names are allowed to filter the results except the ones listed below.
   */
  static get nonFilters() {
    return [<?= rtrim($nonFilterrJS, " ,") ?>];
  }

  /**
   * Validation rules.
   */
  static validatorRules() {
    const rules = {
<?php foreach($rules as $field => $rule): ?>
    <?= "  $field: " . $rule .",\n" ?>
<?php endforeach; ?>
    };

    return rules;
  }

  /**
   * Model relations mapping.
   * Note: These relation mappings are generated for your convenience, you may need to adjust it to work properly.
   */
  static get relationMappings() {
    return {
<?php foreach ($relations as $name => $relation): ?>
      <?= strtolower($name) ?>: {
        relation: <?= $relation[0] ?>,
        modelClass: `${__dirname}/<?= strtolower($name) ?>`,
        join: {
          from: '<?= $relation[0] == 'Api.BelongsToOneRelation' ? strtolower($className) . ".{$relation[3][0]['to']}" : strtolower($name) . ".{$relation[3][0]['from']}" ?>',
          to: '<?= $relation[0] == 'Api.BelongsToOneRelation' ? strtolower($name) . ".{$relation[3][0]['from']}" : strtolower($className) . ".{$relation[3][0]['to']}" ?>',
        },
      },
<?php endforeach; ?>
    };
  }

  // eslint-disable-next-line no-unused-vars
  static $beforeFilter(filters, context) {
    /**
     * Apply any extra filters required based on `context` and you must 
     * always return true to continue filtering otherwise the filters won't be applied.
     * i.e filters.push({key: 'foo', value: 'bar'});
     */
    return true;
  }

  // eslint-disable-next-line no-unused-vars
  static $beforeFind(model, context) {
    /**
     * Add any extra logic here based on the `context`
     * It's good place to add include any relation in the query.
     */
    <?php if (!empty($relations)): ?>
      <?php
        $eagerRelations = [];
        foreach ($relations as $name => $relation): 
          $eagerRelations[] = strtolower($name);
        endforeach; 
      ?>

      /**
       * Fetch necessary relations.
       */
      //  <?= "model.eager('[" . implode(', ', $eagerRelations) . "]');" ?>

    <?php endif; ?>
  }

  static async ApiResponse(item, context) {
    return super.ApiResponse(item, context);
  }
};