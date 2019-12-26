const _ = require('lodash');
const Joi = require('@hapi/joi');
const Constants = require('../../commons/constants');
const checkIfExists = require('./../../policies/checkIfExists');
const User = require('./../../models/squeezeuser');
const Model = require('<?= $modelPath ?>');

const rules = Model.validatorRules();

const handler = async (request, _h) => {
  const r = {
    params: request.params,
    query: request.query,
    payload: request.payload,
    auth: request.auth,
  };
  const result = await Model.view(r);

  return result;
};

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
        policies: [
          checkIfExists(User, 'User', ['id'], ['auth.credentials.userId']),
        ],
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
