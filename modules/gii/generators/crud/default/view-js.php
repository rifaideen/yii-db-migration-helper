const _ = require('lodash');
const Joi = require('@hapi/joi');
const Constants = require('../../commons/constants');
const Model = require('<?= $modelPath ?>.js');

const rules = Model.validatorRules();

const handler = async (request, _h) => await Model.view(request);

// eslint-disable-next-line no-unused-vars
const operation = server => {
  const details = {
    method: ['GET'],
    path: '<?= $paths['view'] ?>',
    options: {
      auth: <?= $isPublicAPI ? 'Constants.AUTH.ALL' : 'Constants.AUTH.ADMIN_OR_USER' ?>,
      description: 'View <?= $model ?> by id - Access - <?= $isPublicAPI ? 'All' : 'Authenticated' ?>',
      tags: ['api'],
      validate: {
        params: Joi.object({
          id: rules.id.required(),
        }),
      },
      plugins: {
        'hapi-swagger': {
          responses: _.omit(Constants.API_STATUS_CODES, [201]),
        },
        policies: [],
      },
      handler,
    },
  };
  return details;
};

module.exports = {
  enabled: true,
  operation,
};
