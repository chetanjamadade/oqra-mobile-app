angular.module('starter.controllers', ['timer'])

    .controller('AppCtrl', function($scope) {
    })

    .controller('NewuserCtrl',function($ionicHistory,$scope,$rootScope,$http,$location,localStorageService,customer,$timeout,locations,texts,$ionicModal) {

        /** Orientation change **/
        $scope.orientClass = false;
        if (window.orientation == 90) {
            $scope.orientClass = true;
        } else if (window.orientation == -90) {
            $scope.orientClass = true;
        } else if (window.orientation == 0) {
            $scope.orientClass = false;
        } else {
            $scope.orientClass = false;
        }

        function setTimer() {
            if (texts.cont != 0) {
                $scope.$on('IdleStart', function() {
                    $location.path('/app/dash');
                });
            }
        }

        $scope.terms = '';

        var fired = false;
        var body_click = false;

        window.addEventListener('orientationchange',function() {
            if (window.orientation == 90) {
                $scope.orientClass = true;
            } else if (window.orientation == -90) {
                $scope.orientClass = true;
            } else if (window.orientation == 0) {
                $scope.orientClass = false;
            } else {
                $scope.orientClass = false;
            }
            $scope.$apply();
        });

        $scope.$on('$ionicView.beforeEnter',function() {
            setTimer();
            $scope.newuser = {};
            window.addEventListener('native.keyboardhide', handleKB_new);
            window.addEventListener('native.keyboardshow',handleKBshow_new);
            $scope.terms = texts.terms;
            $scope.newuser.Terms = true;
        });

        /** Detach events **/
        $scope.$on('$ionicView.leave',function() {
            window.removeEventListener('native.keyboardshow',handleKBshow_new,false);
            window.removeEventListener('native.keyboardhide',handleKB_new,false);
        });

        function handleKB_new(e) {
            if (body_click) {
                body_click = false;
                return;
            }
            //$scope.startSurvey();
        }

        function handleKBshow_new() {
            body_click = false;
        }

        $scope.bodyClick = function() {
            body_click = true;
        };

        $scope.newuser = {};
        $scope.newuser.client_id = localStorageService.get('client_id');
        $scope.newuser.Terms = true;

        $scope.showTerms = function() {
            $rootScope.showAlert(texts.terms);
        };

        $scope.startSurvey = function() {
            $scope.newuser.client_id = localStorageService.get('client_id');
            if (!$scope.newuser.Firstname || !$scope.newuser.Lastname || !$scope.newuser.Cell) {
                $rootScope.showAlert('First, Last and Cell phone are required');
            } else {
                if ($scope.newuser.Email) {
                    if (!$scope.isValidEmailAddress($scope.newuser.Email)) {
                        $rootScope.showAlert('The e-mail you have entered is in invalid format.');
                        return;
                    }
                }

                if (!$scope.newuser.Terms) {
                    $rootScope.showAlert('You must accept terms and condition');
                    return;
                }

                $rootScope.showLoading();
                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=new',
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: { data:$scope.newuser }
                })
                    .success(function(data) {
                        $rootScope.hideLoading();
                        if (data.status == false) {
                            //$rootScope.showAlert(data.msg,false,0);
                            navigator.notification.confirm(data.msg, function(buttonIndex) {
                                switch(buttonIndex) {
                                    case 1:
                                        customer.phone = $scope.newuser.Cell;
                                        $scope.goExisting();
                                        break;
                                    case 2:
                                        customer.phone = '';
                                        break;
                                }
                            }, "Warning", [ "Yes", "No" ]);
                        } else {
                            localStorageService.set('guest_id', data.id);
                            if (locations.number == 1) {
                                localStorageService.set('rating_location',locations.location_id);
                                if(locations.total_professionals > 0){
                                    $location.path('/app/professional');
                                }else {
                                    $location.path('/app/rate');
                                }
                            } else {
                                $location.path('/app/location');
                            }
                        }
                    })
                    .error(function(error) {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                    });
            }
        };

        $scope.goExisting = function() {
            $timeout(function() {
                $location.path('/app/existing');
            },100);
        };

        $scope.goBack = function() {
            $ionicHistory.goBack();
        };

        $scope.isValidEmailAddress = function(emailAddress) {
            var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\]?$)/i);
            return pattern.test(emailAddress);
        };
    })

    .controller('RateCtrl',function(texts,$ionicHistory,$timeout,$scope,$rootScope,$http,$location,localStorageService,customer) {

        $scope.customerData = {};

        $scope.$on('$ionicView.beforeEnter',function() {
            $scope.customerData = {};
            $scope.customerData.firstname = customer.firstname;
            $scope.customerData.lastname = customer.lastname;
            $scope.questionNow = texts.question;
            setTimer();
        });

        $scope.continueBtn = function(rate,text) {
            $timeout(function() {
                localStorageService.set('rating', rate);
                localStorageService.set('ratetext',text);
            },100);

            $timeout(function() {
                if (parseInt(rate) < 4) {
                    $location.path('/app/dissatisfied');
                } else {
                    $location.path('/app/share');
                }
            },120);
        };

        function setTimer() {
            if (texts.cont != 0) {
                $scope.$on('IdleStart', function() {
                    $location.path('/app/dash');
                });
            }
        }

        $scope.goBack = function() {
            $ionicHistory.goBack();
        };
    })

    .controller('CommentsCtrl',function(texts,$scope,$rootScope,$http,$location,localStorageService) {
        $scope.$on('$ionicView.beforeEnter',function() {
            $scope.your_rate = localStorageService.get('rating');
            $scope.rate_text = localStorageService.get('ratetext');
            $scope.exp = {};
            $scope.exp.userid = localStorageService.get('userid');
            $scope.exp.rate = localStorageService.get('rating');
            $scope.exp.ratingid = localStorageService.get('rating_id');
            $scope.exp.location = localStorageService.get('rating_location');
            setTimer();
        });

        function setTimer() {
            if (texts.cont != 0) {
                $scope.$on('IdleStart', function() {
                    $location.path('/app/dash');
                });
            }
        }

        $scope.proceed = function() {
            if (!$scope.exp.title || !$scope.exp.desc) {
                $rootScope.showAlert('All fields are required');
            } else {
                $rootScope.showLoading();
                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=dissatisfied',
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: { data:$scope.exp }
                })
                    .success(function(data) {
                        $rootScope.hideLoading();
                        if (data.status == false) {
                            $rootScope.showAlert(data.msg,false,0);
                        } else {
                            $location.path('/app/thanks');
                        }
                    })
                    .error(function(error) {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                    });
            }
        };
    })

    .controller('ThanksCtrl',function($scope,localStorageService,$http,texts,social,url,$rootScope) {
        $scope.$on('$ionicView.beforeEnter',function() {
            $scope.your_rate = localStorageService.get('rating');
            $scope.thanks_text = texts.thanks_text;
            $scope.socialNetworks = social.networks;
            $scope.baseUrl = url.baseUrl;
            //setTimer();
            // Google submit //
            if (texts.google == true) {
                if ($scope.your_rate >= 4) {
                    $scope.submit = {};
                    $scope.submit.userid = localStorageService.get('userid');
                    $scope.submit.client_id = localStorageService.get('client_id');
                    $scope.submit.guest_id = localStorageService.get('guest_id');
                    $scope.submit.rate = localStorageService.get('rating');
                    $scope.submit.location = localStorageService.get('rating_location');
                    $http({
                        url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=sendsms&lid=' + localStorageService.get('rating_location'),
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        data: { data:$scope.submit }
                    });
                }
            }
        });

        function setTimer() {
            if (texts.cont != 0) {
                $scope.$on('IdleStart', function() {
                    $location.path('/app/dash');
                });
            }
        }

        $scope.socialNetworks = social.networks;
        $scope.baseUrl = url.baseUrl;

        //console.log(url.baseUrl);

        $scope.shareit = function(network) {

            /** Pull network url from the server **/
            $scope.data = {};
            $scope.data.type = network;
            $scope.data.client_id = localStorageService.get('client_id');
            $rootScope.showLoading();
            $scope.triggerUrl = false;

            $http({
                url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=loadurl&lid=' + localStorageService.get('rating_location'),
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: { data:$scope.data }
            })
                .success(function(data) {
                    if (data.status) {
                        $scope.submit = {};
                        $scope.submit.type = network;
                        $scope.submit.userid = localStorageService.get('userid');
                        $scope.submit.rate = localStorageService.get('rating');
                        $scope.submit.location = localStorageService.get('rating_location');
                        $scope.submit.client_id = localStorageService.get('client_id');
                        $rootScope.showLoading();
                        $http({
                            url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=logshare',
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: { data:$scope.submit }
                        })
                            .success(function() {
                                $rootScope.hideLoading();
                                window.open(data.link, '_system', 'location=yes');
                            })
                            .error(function() {
                                $rootScope.hideLoading();
                                $rootScope.showAlert('Something Went wrong, please try again');
                            });
                    } else {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                    }
                })
                .error(function() {
                    $rootScope.hideLoading();
                    $rootScope.showAlert('Something Went wrong, please try again');
                });

            $scope.submit = {};
            $scope.submit.type = network;
            $scope.submit.userid = localStorageService.get('userid');
            $scope.submit.guest_id = localStorageService.get('guest_id');
            $scope.submit.rate = localStorageService.get('rating');
            $scope.submit.location = localStorageService.get('rating_location');
            $scope.submit.network = network;
            $scope.submit.client_id = localStorageService.get('client_id');
            $rootScope.showLoading();

            $http({
                url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=sendsocialsms',
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: { data:$scope.submit }
            })
                .success(function() {
                    $rootScope.hideLoading();
                    $rootScope.showAlert('The text message with your review has been sent. Please copy and paste it on the social media site.');
                })
                .error(function() {
                    $rootScope.hideLoading();
                    $rootScope.showAlert('Something Went wrong, please try again');
                });

        };
    })

    .controller('ExistingCtrl',function(texts,$ionicHistory,$scope,$rootScope,$http,$location,localStorageService,$timeout,customer,locations,$ionicModal) {
        $scope.ex = {};
        $scope.results = {};
        var fired = false;
        var body_click = false;
        $scope.ex.Terms = true;

        $scope.$on('$ionicView.beforeEnter',function() {
            setTimer();
            $scope.ex = {};
            $scope.ex.phone = '';
            $scope.results = {};
            $scope.ex.Terms = true;

            window.addEventListener('native.keyboardhide', handleKB);
            window.addEventListener('native.keyboardshow',handleKBshow);

            if (customer.phone != '') {
                $scope.ex.phone = customer.phone;
                customer.phone = '';
                $timeout(function() {
                    $scope.find();
                },200);
            }
        });

        function setTimer() {
            if (texts.cont != 0) {
                $scope.$on('IdleStart', function() {
                    $location.path('/app/dash');
                });
            }
        }

        function handleKB(e) {
            if (body_click) {
                body_click = false;
                return;
            }
            //$scope.find();
        }

        function handleKBshow() {
            body_click = false;
        }

        $scope.bodyClick = function() {
            body_click = true;
        };

        $scope.showTerms = function() {
            $rootScope.showAlert(texts.terms);
        };

        /** Detach events **/
        $scope.$on('$ionicView.leave',function() {
            window.removeEventListener('native.keyboardshow',handleKBshow,false);
            window.removeEventListener('native.keyboardhide',handleKB,false);
        });

        $scope.find = function() {
            $scope.ex.client_id = localStorageService.get('client_id');

            if ($scope.ex.phone == '') {
                $rootScope.showAlert('Please enter the phone number');
                return;
            }
            if (!$scope.ex.Terms) {
                $rootScope.showAlert('You must accept terms and condition');
                return;
            }
            $rootScope.showLoading();
            $http({
                url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=search',
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: { data:$scope.ex }
            })
                .success(function(data) {
                    $rootScope.hideLoading();
                    if (data.status == false) {
                        $rootScope.showAlert(data.msg,false,0);
                    } else {
                        //$scope.results = data.results;
                        customer.firstname = data.firstname;
                        customer.lastname = data.lastname;
                        localStorageService.set('guest_id', data.id);
                        $scope.gotoNext(data.id);
                    }
                })
                .error(function(error) {
                    $rootScope.hideLoading();
                    $rootScope.showAlert('Something Went wrong, please try again');
                });
        };

        $scope.goBack = function() {
            $ionicHistory.goBack();
        };

        $scope.gotoNext = function(userId) {
            $timeout(function() {
                if (locations.number == 1) {
                    localStorageService.set('rating_location',locations.location_id);
                    if(locations.total_professionals > 0){
                        $location.path('/app/professional');
                    }else {
                        $location.path('/app/rate');
                    }
                } else {
                    $location.path('/app/location');
                }
            },100);
        }

        $scope.next = function(id) {
            $timeout(function() {
                //$location.path('/app/rate');
                //$location.path('/app/location');
                if (locations.number == 1) {
                    localStorageService.set('rating_location',locations.location_id);
                    if(locations.total_professionals > 0){
                        $location.path('/app/professional');
                    }else {
                        $location.path('/app/rate');
                    }
                } else {
                    $location.path('/app/location');
                }
            },100);
        };
    })

    .controller('ShareCtrl',function($ionicHistory,$timeout,$scope,$rootScope,$http,$location,localStorageService,$cordovaSocialSharing,texts,social,url,$cordovaCapture,$cordovaFileTransfer) {

        function setTimer() {
            if (texts.cont != 0) {
                $scope.$on('IdleStart', function() {
                    $location.path('/app/dash');
                });
            }
        }

        /** Refresh ratings **/
        $scope.description = '';
        $scope.$on('$ionicView.beforeEnter',function() {
            //setTimer();
            $scope.your_rate = localStorageService.get('rating');
            $scope.rate_text = localStorageService.get('ratetext');
            $scope.rating_id = localStorageService.get('rating_id');
            $scope.userid = localStorageService.get('userid');
            $scope.sessionid = localStorageService.get('session_id');
            $scope.location = localStorageService.get('rating_location');

            $scope.rev = {};
            $scope.rev.your_rate = localStorageService.get('rating');

            if ($scope.your_rate == 1) {
                $scope.description = texts.verydiss_text;
            } else if ($scope.your_rate == 2) {
                $scope.description = texts.diss_text;
            } else if ($scope.your_rate == 3) {
                $scope.description = texts.sati_text;
            } else if ($scope.your_rate == 4) {
                $scope.description = texts.very_text;
            } else {
                $scope.description = texts.extremly_text;
            }

            /* Load social **/
            //$scope.socialNetworks = social.networks;
            //$scope.baseUrl = url.baseUrl;

            $scope.record_txt = 'Record Review';
            $scope.recording = false;
            $scope.voice_record_file = false;
            $scope.video_record_file = false;
            $scope.minutes = '00';
            $scope.seconds = '00';

            $scope.your_rate = localStorageService.get('rating');
            $scope.rev = {};
            $scope.rev.comment = '';
            $scope.rev.your_rate = localStorageService.get('rating');
            $scope.rev.rate_text = localStorageService.get('ratetext');
            $scope.rev.userid = localStorageService.get('userid');
            $scope.rev.location = localStorageService.get('rating_location');
            $scope.f = localStorageService.get('ratetext');

            $scope.write = false;
            $scope.voice = false;
            $scope.video = false;
            $scope.canPlay = false;
            $scope.$broadcast('timer-reset');
        });

        $scope.writereview = function() {
            $location.path('/app/writereview');
        };

        /** When everything else fails use timeout :) **/
        $scope.goTo = function(url) {
            $timeout(function() {
                $location.path(url);
            },400);
        };

        $scope.shareit = function(network) {

            /** Pull network url from the server **/
            $scope.data = {};
            $scope.data.type = network;
            $scope.data.client_id = localStorageService.get('client_id');
            $rootScope.showLoading();
            $scope.triggerUrl = false;

            $http({
                url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=loadurl&lid=' + localStorageService.get('rating_location'),
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: { data:$scope.data }
            })
                .success(function(data) {
                    if (data.status) {
                        $scope.submit = {};
                        $scope.submit.type = network;
                        $scope.submit.userid = localStorageService.get('userid');
                        $scope.submit.rate = localStorageService.get('rating');
                        $scope.submit.location = localStorageService.get('rating_location');
                        $scope.submit.client_id = localStorageService.get('client_id');
                        $rootScope.showLoading();
                        $http({
                            url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=logshare',
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: { data:$scope.submit }
                        })
                            .success(function() {
                                $rootScope.hideLoading();
                                window.open(data.link, '_system', 'location=yes');
                            })
                            .error(function() {
                                $rootScope.hideLoading();
                                $rootScope.showAlert('Something Went wrong, please try again');
                            });
                    } else {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                    }
                })
                .error(function() {
                    $rootScope.hideLoading();
                    $rootScope.showAlert('Something Went wrong, please try again');
                });
        };

        $scope.your_rate = localStorageService.get('rating');
        $scope.rev = {};
        $scope.rev.your_rate = localStorageService.get('rating');
        $scope.rev.rate_text = localStorageService.get('ratetext');
        $scope.rev.rating_id = localStorageService.get('rating_id');
        $scope.rev.userid = localStorageService.get('userid');
        $scope.rev.sessionid = localStorageService.get('session_id');
        $scope.rev.location = localStorageService.get('rating_location');

        /** Voice recording handle **/
        $scope.record_txt = 'Record Review';
        $scope.recording = false;
        $scope.voice_record_file = false;
        $scope.video_record_file = false;
        $scope.minutes = '00';
        $scope.seconds = '00';

        $scope.record = function() {
            if ($scope.recording == true) {
                window.plugins.audioRecorderAPI.stop(function(msg) {
                    //alert('saved: ' + msg);
                    $scope.canPlay = true;
                    $scope.voice_record_file = msg;
                }, function(msg) {
                    //alert('Error: ' + msg);
                });
                $scope.record_txt = 'Record Review';
                $scope.recording = false;
                $scope.canPlay = true;
                $scope.$broadcast('timer-stop');
                return;
            } else {
                $scope.$broadcast('timer-start');
                $scope.canPlay = false;
                $scope.recording = true;
                $scope.record_txt = 'Stop Recording';
                window.plugins.audioRecorderAPI.record(function(msg) {}, function(msg) {}, 1200);
            }
        }

        /** Upload voice **/
        $scope.upload = function() {
            if (!$scope.voice_record_file) {
                $rootScope.showAlert('Please record review');
                return;
            }
            $rootScope.showLoading();
            var url = 'http://fluohead.com/oqra-dashboard/mobile-oqra/audio_upload/upload.php';
            var target = $scope.voice_record_file;
            var filename = target.split("/").pop();
            var options = {
                fileKey: "file",
                fileName: filename,
                chunkedMode: false
            };

            $scope.rev.your_rate = localStorageService.get('rating');
            $scope.rev.userid = localStorageService.get('userid');
            $scope.rev.location = localStorageService.get('rating_location');
            $scope.rev.client_id = localStorageService.get('client_id');

            $cordovaFileTransfer.upload(url, target, options).then(function(result) {
                $scope.rev.file = $scope.voice_record_file;
                $scope.rev.guest_id = localStorageService.get('guest_id');
                $scope.rev.client_id = localStorageService.get('client_id');
                if(localStorageService.get('rating_professional') != undefined){
                    $scope.rev.professional = localStorageService.get('rating_professional');
                }else{
                    $scope.rev.location = localStorageService.get('rating_location');
                }
                $scope.rev.your_rate = localStorageService.get('rating');
                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=finish',
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: { data:$scope.rev }
                })
                    .success(function(data) {
                        $rootScope.hideLoading();
                        $location.path('/app/thanks');
                    })
                    .error(function(error) {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                    });
            }, function(err) {
                $rootScope.hideLoading();
                $rootScope.showAlert('Something went wrong, please try again');
            }, function(progress) {

            });
        }

        /** Upload video **/
        $scope.uploadVideo = function() {
            if (!$scope.video_record_file) {
                $rootScope.showAlert('Please record review');
                return;
            }
            $rootScope.showLoading();

            $scope.rev.your_rate = localStorageService.get('rating');
            $scope.rev.userid = localStorageService.get('userid');
            $scope.rev.location = localStorageService.get('rating_location');

            var url = 'http://fluohead.com/oqra-dashboard/mobile-oqra/video_upload/upload.php';
            var target = $scope.video_record_file;
            var filename = target.split("/").pop();
            var options = {
                fileKey: "file",
                fileName: filename,
                chunkedMode: false
            };

            $cordovaFileTransfer.upload(url, target, options).then(function(result) {
                $scope.rev.file = target;
                $scope.rev.guest_id = localStorageService.get('guest_id');
                $scope.rev.client_id = localStorageService.get('client_id');
                if(localStorageService.get('rating_professional') != undefined){
                    $scope.rev.professional = localStorageService.get('rating_professional');
                }else{
                    $scope.rev.location = localStorageService.get('rating_location');
                }
                $scope.rev.your_rate = localStorageService.get('rating');
                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=finish',
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: { data:$scope.rev }
                })
                    .success(function(data) {
                        $rootScope.hideLoading();
                        $location.path('/app/thanks');
                    })
                    .error(function(error) {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                        $scope.confirmVideo();
                    });
            }, function(err) {
                $rootScope.hideLoading();
                $rootScope.showAlert('Something went wrong, please try again');
            }, function(progress) {

            });
        };

        $scope.playback = function() {
            window.plugins.audioRecorderAPI.playback();
        }

        $scope.write = false;
        $scope.voice = false;
        $scope.video = false;
        $scope.canPlay = false;

        $scope.writeReview = function() {
            $scope.write = true;
            $scope.voice = false;
            $scope.video = false;
        }

        $scope.recordReview = function() {
            $scope.write = false;
            $scope.voice = true;
            $scope.video = false;
        }

        $scope.videoReview = function() {
            $scope.write = false;
            $scope.voice = false;
            $scope.video = true;
        }

        $scope.confirmVideo = function() {
            navigator.notification.confirm("Do you want to submit your video review ?", function(buttonIndex) {
                switch(buttonIndex) {
                    case 1:
                        $timeout(function() {
                            $scope.uploadVideo();
                        },200);
                        break;
                    case 2:
                        $scope.video_record_file = false;
                        break;
                }
            }, "Submit Review", [ "Yes", "No" ]);
        }

        /** Video Record **/
        $scope.recordVideo = function() {
            var options = { limit: 1, duration: 300 };
            $cordovaCapture.captureVideo(options).then(function(videoData) {
                $scope.video_record_file = videoData[0].fullPath;
                $scope.confirmVideo();
            }, function(err) {
                // Handle error if needed
            });
        }

        $scope.submitReview = function() {
            if(localStorageService.get('rating_professional') != undefined){
                $scope.rev.professional = localStorageService.get('rating_professional');
            }else{
                $scope.rev.location = localStorageService.get('rating_location');
            }
            $scope.rev.client_id = localStorageService.get('client_id');
            if (!$scope.rev.comment) {
                $rootScope.showAlert('Please enter your review');
                return;
            }
            $rootScope.showLoading();
            $scope.rev.guest_id = localStorageService.get('guest_id');
            $http({
                url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=submitreview',
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: { data:$scope.rev }
            })
                .success(function(data) {
                    $rootScope.hideLoading();
                    $location.path('/app/thanks');
                })
                .error(function(error) {
                    $rootScope.hideLoading();
                    $rootScope.showAlert('Something Went wrong, please try again');
                });
        };

        $scope.goBack = function() {
            $ionicHistory.goBack();
        };
    })

    .controller('ReviewCtrl',function($ionicHistory,$timeout,$scope,$rootScope,$http,$location,localStorageService,$cordovaCapture,$cordovaFileTransfer) {
        $scope.your_rate = localStorageService.get('rating');
        $scope.rev = {};
        $scope.rev.your_rate = localStorageService.get('rating');
        $scope.rev.rate_text = localStorageService.get('ratetext');
        $scope.rev.rating_id = localStorageService.get('rating_id');
        $scope.rev.userid = localStorageService.get('userid');
        $scope.rev.sessionid = localStorageService.get('session_id');
        $scope.rev.location = localStorageService.get('rating_location');

        /** Voice recording handle **/
        $scope.record_txt = 'Record Review';
        $scope.recording = false;
        $scope.voice_record_file = false;
        $scope.video_record_file = false;
        $scope.minutes = '00';
        $scope.seconds = '00';

        $scope.$on('$ionicView.beforeEnter',function() {
            $scope.record_txt = 'Record Review';
            $scope.recording = false;
            $scope.voice_record_file = false;
            $scope.video_record_file = false;
            $scope.minutes = '00';
            $scope.seconds = '00';

            $scope.your_rate = localStorageService.get('rating');
            $scope.rev = {};
            $scope.rev.comment = '';
            $scope.rev.your_rate = localStorageService.get('rating');
            $scope.rev.rate_text = localStorageService.get('ratetext');
            $scope.rev.userid = localStorageService.get('userid');
            $scope.rev.location = localStorageService.get('rating_location');
            $scope.f = localStorageService.get('ratetext');

            $scope.write = false;
            $scope.voice = false;
            $scope.video = false;
            $scope.canPlay = false;
            $scope.$broadcast('timer-reset');

            window.addEventListener('native.keyboardhide', handleKB_review);
            window.addEventListener('native.keyboardshow',handleKBshow_review);
        });

        var fired = false;
        var body_click = false;

        /** Detach events **/
        $scope.$on('$ionicView.leave',function() {
            window.removeEventListener('native.keyboardshow',handleKBshow_review,false);
            window.removeEventListener('native.keyboardhide',handleKB_review,false);
        });

        function handleKB_review(e) {
            if (body_click) {
                body_click = false;
                return;
            }
            $scope.submitReview();
        }

        function handleKBshow_review() {
            body_click = false;
        }

        $scope.bodyClick = function() {
            body_click = true;
        };

        $scope.record = function() {
            if ($scope.recording == true) {
                window.plugins.audioRecorderAPI.stop(function(msg) {
                    //alert('saved: ' + msg);
                    $scope.canPlay = true;
                    $scope.voice_record_file = msg;
                }, function(msg) {
                    //alert('Error: ' + msg);
                });
                $scope.record_txt = 'Record Review';
                $scope.recording = false;
                $scope.canPlay = true;
                $scope.$broadcast('timer-stop');
                return;
            } else {
                $scope.$broadcast('timer-start');
                $scope.canPlay = false;
                $scope.recording = true;
                $scope.record_txt = 'Stop Recording';
                window.plugins.audioRecorderAPI.record(function(msg) {}, function(msg) {}, 1200);
            }
        }

        /** Upload voice **/
        $scope.upload = function() {
            if (!$scope.voice_record_file) {
                $rootScope.showAlert('Please record review');
                return;
            }
            $rootScope.showLoading();
            var url = 'http://fluohead.com/oqra-dashboard/mobile-oqra/audio_upload/upload.php';
            var target = $scope.voice_record_file;
            var filename = target.split("/").pop();
            var options = {
                fileKey: "file",
                fileName: filename,
                chunkedMode: false
            };

            $scope.rev.your_rate = localStorageService.get('rating');
            $scope.rev.userid = localStorageService.get('userid');
            $scope.rev.location = localStorageService.get('rating_location');

            $cordovaFileTransfer.upload(url, target, options).then(function(result) {
                $scope.rev.file = $scope.voice_record_file;
                $scope.rev.guest_id = localStorageService.get('guest_id');
                $scope.rev.client_id = localStorageService.get('client_id');
                if(localStorageService.get('rating_professional') != undefined){
                    $scope.rev.professional = localStorageService.get('rating_professional');
                }else{
                    $scope.rev.location = localStorageService.get('rating_location');
                }
                $scope.rev.your_rate = localStorageService.get('rating');
                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=finish',
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: { data:$scope.rev }
                })
                    .success(function(data) {
                        $rootScope.hideLoading();
                        $location.path('/app/thanks');
                    })
                    .error(function(error) {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                    });
            }, function(err) {
                $rootScope.hideLoading();
                $rootScope.showAlert('Something went wrong, please try again');
            }, function(progress) {

            });
        }

        /** Upload video **/
        $scope.uploadVideo = function() {
            if (!$scope.video_record_file) {
                $rootScope.showAlert('Please record review');
                return;
            }
            $rootScope.showLoading();

            $scope.rev.your_rate = localStorageService.get('rating');
            $scope.rev.userid = localStorageService.get('userid');
            $scope.rev.location = localStorageService.get('rating_location');

            var url = 'http://fluohead.com/oqra-dashboard/mobile-oqra/video_upload/upload.php';
            var target = $scope.video_record_file;
            var filename = target.split("/").pop();
            var options = {
                fileKey: "file",
                fileName: filename,
                chunkedMode: false
            };

            $cordovaFileTransfer.upload(url, target, options).then(function(result) {
                $scope.rev.file = target;
                $scope.rev.guest_id = localStorageService.get('guest_id');
                $scope.rev.client_id = localStorageService.get('client_id');
                if(localStorageService.get('rating_professional') != undefined){
                    $scope.rev.professional = localStorageService.get('rating_professional');
                }else{
                    $scope.rev.location = localStorageService.get('rating_location');
                }
                $scope.rev.your_rate = localStorageService.get('rating');
                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=finish',
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    data: { data:$scope.rev }
                })
                    .success(function(data) {
                        $rootScope.hideLoading();
                        $location.path('/app/thanks');
                    })
                    .error(function(error) {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                        $scope.confirmVideo();
                    });
            }, function(err) {
                $rootScope.hideLoading();
                $rootScope.showAlert('Something went wrong, please try again');
            }, function(progress) {

            });
        };

        $scope.playback = function() {
            window.plugins.audioRecorderAPI.playback();
        }

        $scope.write = false;
        $scope.voice = false;
        $scope.video = false;
        $scope.canPlay = false;

        $scope.writeReview = function() {
            $scope.write = true;
            $scope.voice = false;
            $scope.video = false;
        }

        $scope.recordReview = function() {
            $scope.write = false;
            $scope.voice = true;
            $scope.video = false;
        }

        $scope.videoReview = function() {
            $scope.write = false;
            $scope.voice = false;
            $scope.video = true;
        }

        $scope.confirmVideo = function() {
            navigator.notification.confirm("Do you want to submit your video review ?", function(buttonIndex) {
                switch(buttonIndex) {
                    case 1:
                        $timeout(function() {
                            $scope.uploadVideo();
                        },200);
                        break;
                    case 2:
                        $scope.video_record_file = false;
                        break;
                }
            }, "Submit Review", [ "Yes", "No" ]);
        }

        /** Video Record **/
        $scope.recordVideo = function() {
            var options = { limit: 1, duration: 300 };
            $cordovaCapture.captureVideo(options).then(function(videoData) {
                $scope.video_record_file = videoData[0].fullPath;
                $scope.confirmVideo();
            }, function(err) {
                // Handle error if needed
            });
        }

        $scope.submitReview = function() {
            if(localStorageService.get('rating_professional') != undefined){
                $scope.rev.professional = localStorageService.get('rating_professional');
            }else{
                $scope.rev.location = localStorageService.get('rating_location');
            }
            $scope.rev.client_id = localStorageService.get('client_id');
            if (!$scope.rev.comment) {
                $rootScope.showAlert('Please enter your review');
                return;
            }
            $rootScope.showLoading();
            $scope.rev.guest_id = localStorageService.get('guest_id');
            $http({
                url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=submitreview',
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: { data:$scope.rev }
            })
                .success(function(data) {
                    $rootScope.hideLoading();
                    $location.path('/app/thanks');
                })
                .error(function(error) {
                    $rootScope.hideLoading();
                    $rootScope.showAlert('Something Went wrong, please try again');
                });
        };

        $scope.goBack = function() {
            $ionicHistory.goBack();
        };

    })

    .controller('LocationCtrl',function($rootScope,$scope,$http,$cordovaGeolocation,$ionicHistory,$location,$timeout,localStorageService,texts,social) {
        $scope.$on('$ionicView.afterEnter',function() {
            setTimer();
            check();
            localStorageService.set('rating_location','');
            $scope.error = false;
        });

        function setTimer() {
            if (texts.cont != 0) {
                $scope.$on('IdleStart', function() {
                    $location.path('/app/dash');
                });
            }
        }

        $scope.$on('$ionicView.leave',function() {
            $scope.results = {};
            $scope.no_location = 'Searching nearest location, please wait ...';
            $scope.error = false;
        })

        $scope.results = {};
        $scope.no_location = 'Searching nearest location, please wait ...';
        $scope.error = false;

        $scope.goBack = function() {
            $ionicHistory.goBack();
        };

        function check() {
            $scope.no_location = 'Searching nearest location, please wait ...';
            var opts = {timeout:15000,enableHighAccuracy:true};
            $cordovaGeolocation.getCurrentPosition(opts).then(function(pos) {
                //$rootScope.showAlert(pos.coords.latitude + ' ' + pos.coords.longitude);
                $scope.gps = {};
                $scope.gps.lon = pos.coords.longitude; //-98.479038//pos.coords.longitude;
                $scope.gps.lat = pos.coords.latitude; //29.394808//pos.coords.latitude;
                $scope.gps.client_id = localStorageService.get('client_id');

                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=closest',
                    method: 'POST',
                    data: { data:$scope.gps },
                    headers: { 'Content-Type' : 'application/x-www-form-urlencoded' },
                    timeout: 4000
                })
                    .success(function(data) {
                        if (data.status == false) {
                            $scope.no_location = data.msg;
                            $scope.error = true;
                        } else {
                            $scope.results = data.results;
                            $scope.error = false;
                        }
                    })
                    .error(function(data) {
                        $rootScope.showAlert('Unable to get locations. Please check your internet connection and try again');
                        $scope.no_location = 'No internet connection, check your settings and try again.';
                        $scope.error = true;
                    });
            },function(error) {
                $scope.gps = {};
                $scope.gps.client_id = localStorageService.get('client_id');
                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=fallbackgps',
                    method: 'POST',
                    headers: { 'Content-Type' : 'application/x-www-form-urlencoded' },
                    timeout: 4000,
                    data: { data:$scope.gps }
                })
                    .success(function(data) {
                        if (data.status == false) {
                            $scope.no_location = data.msg;
                            $scope.error = true;
                        } else {
                            $scope.results = data.results;
                            $scope.error = false;
                        }
                    });
            });
        }

        $scope.reload = function() {
            check();
        };

        $scope.chooseLocation = function(id) {
            $scope.loaddata = {};
            $scope.loaddata.client_id = localStorageService.get('client_id');
            $timeout(function() {
                localStorageService.set('rating_location',id);

                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=loadlocationconf&lid=' + id,
                    method: 'POST',
                    headers: { 'Content-Type' : 'application/x-www-form-urlencoded' },
                    data: { data:$scope.loaddata }
                })
                    .success(function(data) {
                        if(data.type == "location") {
                            //texts.extremly_text = data.data.ex_satisfied_txt;
                            texts.very_text = data.data.very_satisfied_text;
                            texts.sati_text = data.data.satisfied_text;
                            texts.diss_text = data.data.dissatisfied_text;
                            texts.verydiss_text = data.data.very_dissat_text;
                            texts.thanks_text = data.data.thank_you;
                            texts.timeout = data.timeout;
                            texts.question = data.question;
                            texts.google = data.google;
                            localStorageService.set('rating_professional','');

                            /** Load social share **/
                            if (data.social) {
                                social.networks = data.social;
                            }
                            $location.path('/app/rate');
                        }else if (data.type == 'professional'){
                            $location.path('/app/professional');
                        }
                    })
                    .error(function(error) {
                        $rootScope.showAlert('Unable to communicate with the server. Please check your internet connection.');
                    });
            },100);
        };

    })

    .controller('ProfessionalCtrl',function($rootScope,$scope,$http,$cordovaGeolocation,$ionicHistory,$location,$timeout,localStorageService,texts,social) {
        $scope.$on('$ionicView.afterEnter',function() {
            setTimer();
            loadprofessional();
            localStorageService.set('rating_professional','');
            $scope.error = false;
        });

        function setTimer() {
            if (texts.cont != 0) {
                $scope.$on('IdleStart', function() {
                    $location.path('/app/dash');
                });
            }
        }

        function loadprofessional(){
            $scope.emp = {};
            $scope.emp.client_id = localStorageService.get('client_id');
            var loc_id = localStorageService.get('rating_location');
            $http({
                url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=loadprofessional&lid='+loc_id,
                method: 'POST',
                headers: { 'Content-Type' : 'application/x-www-form-urlencoded' },
                timeout: 4000,
                data: { data:$scope.emp }
            })
                .success(function(data) {
                    if (data.status == false) {
                        $scope.no_professional = data.msg;
                        $scope.error = true;
                    } else {
                        $scope.results = data.results;
                        $scope.error = false;
                    }
                });
        }

        $scope.$on('$ionicView.leave',function() {
            $scope.results = {};
            $scope.no_professional = 'Searching professionals, please wait ...';
            $scope.error = false;
        })

        $scope.results = {};
        $scope.no_professional = 'Searching professionals, please wait ...';
        $scope.error = false;


        $scope.goBack = function() {
            $ionicHistory.goBack();
        };



        $scope.chooseProfessional = function(id) {
            $scope.loaddata = {};
            $scope.loaddata.client_id = localStorageService.get('client_id');
            var loc_id = localStorageService.get('rating_location');
            localStorageService.set('rating_professional',id);
            $timeout(function() {

                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=loadlocationconf&lid=' + loc_id + '&eid=' +id,
                    method: 'POST',
                    headers: { 'Content-Type' : 'application/x-www-form-urlencoded' },
                    data: { data:$scope.loaddata }
                })
                    .success(function(data) {   //texts.extremly_text = data.data.ex_satisfied_txt;
                        texts.very_text = data.data.very_satisfied_text;
                        texts.sati_text = data.data.satisfied_text;
                        texts.diss_text = data.data.dissatisfied_text;
                        texts.verydiss_text = data.data.very_dissat_text;
                        texts.thanks_text = data.data.thank_you;
                        texts.timeout = data.timeout;
                        texts.question = data.question;
                        texts.google = data.google;
                        /** Load social share **/
                        if (data.social) {
                            social.networks = data.social;
                        }
                        $location.path('/app/rate');
                    })
                    .error(function(error) {
                        $rootScope.showAlert('Unable to communicate with the server. Please check your internet connection.');
                    });
            },100);
        };

    })

    .controller('LoginCtrl', function(Idle,$rootScope,$scope,$cordovaNetwork,$location,$timeout,$ionicPlatform,$http, texts, locations, localStorageService, social, url, $cordovaGeolocation) {
       if(window.localStorage["session"] != undefined){
            $location.path('/app/dash');
        }else {
            $scope.client = {};
            $scope.login = function () {

                if ($scope.client.Email == undefined || $scope.client.Password == undefined) {
                    $rootScope.showAlert('Please fill email and password fields!');
                    return;
                }
                if ($scope.client.Email) {
                    if (!$scope.isValidEmailAddress($scope.client.Email)) {
                        $rootScope.showAlert('The e-mail you have entered is in invalid format.');
                        return;
                    }
                }

                $rootScope.showLoading();
                $http({
                    url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=login',
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: {data: $scope.client}
                })
                    .success(function (data) {
                        $rootScope.hideLoading();
                        if (data.status == false) {
                            $rootScope.showAlert(data.msg, false, 0);
                        } else {
                            localStorageService.set('client_id', +data.client_id);
                            localStorageService.set('userid', +data.user_id);
                            texts.terms = data.terms;
                            window.localStorage["session"] = JSON.stringify($scope.client);
                            $location.path('/app/dash');
                        }
                    })
                    .error(function (error) {
                        $rootScope.hideLoading();
                        $rootScope.showAlert('Something Went wrong, please try again');
                    });
            };

            $scope.isValidEmailAddress = function (emailAddress) {
                var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\]?$)/i);
                return pattern.test(emailAddress);
            };
        }
    })

    .controller('DashCtrl', function(Idle,$rootScope,$scope,$cordovaNetwork,$location,$timeout,$ionicPlatform,$http, texts, locations, localStorageService, social, url, $cordovaGeolocation) {
        $scope.results = {};
        $ionicPlatform.ready(function() {
            texts.cont = 'dash';
            check();
            var opts = {timeout:15000,enableHighAccuracy:true};
            $cordovaGeolocation.getCurrentPosition(opts).then(function(pos) {
            });
        });

        function check() {
            $scope.loaddata = {};
            $scope.loaddata.client_id = localStorageService.get('client_id');
            /*if ($cordovaNetwork.getNetwork() == 'none') {
             $rootScope.showAlert('You are not connected to the Internet.');
             } else {*/
            $http({
                url: 'http://fluohead.com/oqra-dashboard/admin/inc/functions/mobileUp.php?key=test&route=loadlocationconf',
                method: 'POST',
                headers: { 'Content-Type' : 'application/x-www-form-urlencoded' },
                data: { data:$scope.loaddata  }
            })
                .success(function(data) {
                    url.baseUrl = data.url;
                    /** Number of locations **/
                    texts.terms = data.terms;
                    locations.number = data.total_locations;
                    locations.total_professionals = +data.total_professionals;
                    locations.location_id = +data.location_id;
                    $scope.results.logo =  "gfx/logo.png";
                    if(data.logo != null){
                        $scope.results.logo = data.logo;
                    }
                    $scope.results.new_text = "New";
                    if(data.new_text != null){
                        $scope.results.new_text = data.new_text;
                    }   

                    $scope.results.existing_text = "Existing";
                    if(data.existing_text != null){
                        $scope.results.existing_text = data.existing_text;
                    }                                       
                })
                .error(function(error) {
                    $rootScope.showAlert('Unable to communicate with the server. Please check your internet connection.');
                });
            //}
        }

        $scope.$on('$ionicView.leave',function() {
            //document.removeEventListener("resume",check,false);
        });

        $scope.$on('$ionicView.beforeEnter',function() {
            localStorageService.set('rating','');
            localStorageService.set('ratetext','');
            localStorageService.set('rating','');
            localStorageService.set('rating_id','');
            localStorageService.set('rating_location','');
            //document.addEventListener("resume",check);
        });
    });