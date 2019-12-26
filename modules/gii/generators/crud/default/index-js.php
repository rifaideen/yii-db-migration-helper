const _ = require('lodash');
const Joi = require('@hapi/joi');
const Constants = require('../../commons/constants');
const checkIfExists = require('./../../policies/checkIfExists');
const User = require('./../../models/squeezeuser');
const Model = require('<?= $modelPath ?>');

/**
 * API request handler
 */
const handler = async (request, _h) => await Model.index(request);

// eslint-disable-next-line no-unused-vars
const operation = server => {
  const details = {
    method: ['GET'],
    path: '<?= $paths['index'] ?>',
    options: {
      auth: <?= $isPublicAPI ? 'Constants.AUTH.ALL' : 'Constants.AUTH.ADMIN_OR_USER' ?>,
      description: 'List All <?= $model ?> - Access - <?= $isPublicAPI ? 'All' : 'Authenticated' ?>',
      tags: ['api'],
      validate: {
        query: Joi.object({
          limit: Joi.number()
            .integer()
            .positive()
            .min(1)
            .max(50)
            .default(20)
            .description('Limit')
            .optional(),
          offset: Joi.number()
            .integer()
            .min(0)
            .default(0)
            .description('Offset')
            .optional(),
          sortField: Joi.string()
            .trim()
            .description('Sort field name name.')
            .optional(),
          sortOrder: Joi.string()
            .trim()
            .description('Sort order. asc | desc')
            .optional(),
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
