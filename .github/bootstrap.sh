#!/bin/bash
# https://docs.github.com/en/actions/learn-github-actions/contexts#example-usage-of-the-github-context
# https://docs.github.com/en/actions/learn-github-actions/variables#using-the-vars-context-to-access-configuration-variable-values
# https://docs.github.com/en/actions/learn-github-actions/variables#default-environment-variables
if [ "${GITHUB_EVENT_NAME}" == 'pull_request' ]; then
  # The head ref or source branch of the pull request in a workflow run.
  # This property is only set when the event that triggers a workflow run is either pull_request or pull_request_target.
  # For example, feature-branch-1
  BUILD_BRANCH=${GITHUB_HEAD_REF}
  # The name of the base ref or target branch of the pull request in a workflow run.
  # This is only set when the event that triggers a workflow run is either pull_request or pull_request_target.
  # For example, main
  BUILD_TARGET_BRANCH=${GITHUB_BASE_REF}
  echo -e "\nPull request from the branch (${BUILD_BRANCH}) to the branch (${BUILD_TARGET_BRANCH})"
else
  # The short ref name of the branch or tag that triggered the workflow run.
  # This value matches the branch or tag name shown on GitHub.
  # For example, feature-branch-1 or 57/merge for a pull request
  BUILD_BRANCH=${GITHUB_REF_NAME}
fi

# ${TRAVIS_BRANCH} in a PR build is the target branch
# ${TRAVIS_PULL_REQUEST_BRANCH} is not empty in a PR build
#if [ "${TRAVIS_PULL_REQUEST_BRANCH}" == "" ]; then
#  BUILD_BRANCH=${TRAVIS_BRANCH}
#  echo -e "\nPush Build on the branch (${BUILD_BRANCH})"
#else
#  BUILD_BRANCH=${TRAVIS_PULL_REQUEST_BRANCH}
#  echo -e "\nPull Request Build from the branch (${TRAVIS_PULL_REQUEST_BRANCH})"
#fi

echo -e "\nGet boot.sh from from the branch (${BUILD_BRANCH})"
url="https://raw.githubusercontent.com/ComboStrap/combo_test/${BUILD_BRANCH}/resources/script/ci/boot.sh"
if [ -z "$TOKEN" ]; then
  echo 'The token is mandatory and was not found'
  exit 1
fi
response=$(curl -H "Authorization: token ${TOKEN}" -s -w "%{http_code}" -o "boot.sh" "$url")
# -s silence
# -w ask to print the http code
# -o write the body to this file
if [ "$response" != "200" ]; then
  echo "Error when getting the boot.sh script at $url."
  echo "HTTP status was not 200 but $response"
  exit 1
else
  echo "boot.sh successfully downloaded"
fi
echo -e "\nRun boot.sh"
chmod +x boot.sh
source boot.sh
