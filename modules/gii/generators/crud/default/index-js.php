const _ = require('lodash');
const Constants = require('../../commons/constants');
const Model = require('<?= $modelPath ?>.js');

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
      description: 'List all <?= $model ?> - Access - <?= $isPublicAPI ? 'All' : 'Authenticated' ?>',
      tags: ['api'],
      validate: {},
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
