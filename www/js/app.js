angular.module('starter', ['ionic','ngIdle','ngMask','ngCordova','LocalStorageModule','starter.controllers','ionic.rating'])

.value('texts', {
    extremly_text : '',
    very_text : '',
    sati_text : '',
    diss_text : '',
    verydiss_text : '',
    thanks_text : '',
    terms : '',
    cont : '',
    question: '',
    google: false,
    dictionary: []
})

.value('configuration', {
    language: 'eng'
})

.value('social', {
    networks: {}
})

.value('url', {
    baseUrl : ''
})

.value('customer', {
    phone: '',
    firstname: '',
    lastname: ''
})

.value('locations', {
    number: 0,
    location_id: 0,
    total_professionals: 0
})

.config(['$sceDelegateProvider', function($sceDelegateProvider) {
    $sceDelegateProvider.resourceUrlWhitelist(['self', 'http://fluohead.com/**']);
}])

.config(['$httpProvider', function($httpProvider) {
    $httpProvider.defaults.useXDomain = true;
    delete $httpProvider.defaults.headers.common['X-Requested-With'];
}])

.config(function(IdleProvider, KeepaliveProvider) {
    // configure Idle settings
    IdleProvider.idle(180); // in seconds
    IdleProvider.timeout(0); // in seconds
    KeepaliveProvider.interval(2); // in seconds
})

.run(function($ionicPlatform,$rootScope,$ionicPopup,$ionicLoading,Idle) {
    Idle.watch();
    $ionicPlatform.ready(function() {
        if (window.cordova && window.cordova.plugins.Keyboard) {
            cordova.plugins.Keyboard.hideKeyboardAccessoryBar(false);
            cordova.plugins.Keyboard.disableScroll(true);
        }
        if (window.StatusBar) {
            StatusBar.styleDefault();
        }
    });
    
    $rootScope.showAlert = function(text,one,two) {
        $ionicPopup.alert({
            title: text
        });
    };

    $rootScope.showAlertDone = function(text,one,two) {
        $ionicPopup.alert(
            text, 
            null, 
            'Information',
            'Done'
        );
    };
    
    /** Loading window **/
    $rootScope.showLoading = function() {
        $ionicLoading.show({
            template: '<ion-spinner icon="ios"></ion-spinner>'
        });
    };
    
    $rootScope.hideLoading = function() {
        $ionicLoading.hide();
    };
    
})

.config(function($stateProvider, $urlRouterProvider) {
  
    $stateProvider
        .state('app', {
            url: '/app',
            abstract: true,
            templateUrl: 'templates/menu.html'
    })
    
    .state('app.newuser', {
        url: '/newuser',
        views: {
            'noMenuView': {
            templateUrl: 'templates/newuser.html',
            controller: 'NewuserCtrl'
            }
        }
    })
    
    .state('app.rate', {
        url: '/rate',
        views: {
            'noMenuView': {
            templateUrl: 'templates/rate.html',
            controller: 'RateCtrl'
            }
        }
    })
    
    .state('app.comments', {
        url: '/comments',
        views: {
            'noMenuView': {
            templateUrl: 'templates/comments.html',
            controller: 'CommentsCtrl'
            }
        }
    })

    .state('app.location', {
        url: '/location',
        views: {
            'noMenuView': {
            templateUrl: 'templates/location.html',
            controller: 'LocationCtrl'
            }
        }
    })

    .state('app.professional', {
        url: '/professional',
        views: {
            'noMenuView': {
                templateUrl: 'templates/professional.html',
                controller: 'ProfessionalCtrl'
            }
        }
    })

    .state('app.thanks', {
        url: '/thanks',
        views: {
            'noMenuView': {
            templateUrl: 'templates/thanks.html',
            controller: 'ThanksCtrl'
            }
        }
    })
    
    .state('app.writereview', {
        url: '/writereview',
        views: {
            'noMenuView': {
            templateUrl: 'templates/writereview.html',
            controller: 'ReviewCtrl'
            }
        }
    })

    .state('app.pickreview', {
        url: '/pickreview',
        views: {
            'noMenuView': {
            templateUrl: 'templates/pickreview.html',
            controller: 'ReviewCtrl'
            }
        }
    })

    .state('app.audioreview', {
        url: '/audioreview',
        views: {
            'noMenuView': {
            templateUrl: 'templates/audioreview.html',
            controller: 'ReviewCtrl'
            }
        }
    })

    .state('app.videoreview', {
        url: '/videoreview',
        views: {
            'noMenuView': {
            templateUrl: 'templates/videoreview.html',
            controller: 'ReviewCtrl'
            }
        }
    })
    
    .state('app.existing', {
        url: '/existing',
        views: {
            'noMenuView': {
            templateUrl: 'templates/existing.html',
            controller: 'ExistingCtrl'
            }
        }
    })

    .state('app.noresults', {
        url: '/noresults',
        views: {
            'noMenuView': {
            templateUrl: 'templates/noresults.html',
            controller: 'ExistingCtrl'
            }
        }
    })
    
    .state('app.profile', {
        url: '/profile',
        views: {
            'noMenuView': {
            templateUrl: 'templates/profile.html',
            controller: 'ExistingCtrl'
            }
        }
    })

    .state('app.share', {
        url: '/share',
        views: {
            'noMenuView': {
            templateUrl: 'templates/share.html',
            controller: 'ShareCtrl'
            }
        }
    })

    .state('app.dissatisfied', {
        url: '/dissatisfied',
        views: {
            'noMenuView': {
            templateUrl: 'templates/dissatisfied.html',
            controller: 'ShareCtrl'
            }
        }
    })
    
    .state('app.dash', {
        url: '/dash',
        views: {
            'noMenuView': {
            templateUrl: 'templates/dash.html',
            controller: 'DashCtrl'
            }
        }
    })

    .state('app.login', {
        url: '/login',
        views: {
            'noMenuView': {
                templateUrl: 'templates/login.html',
                controller: 'LoginCtrl'
            }
        }
    })
    
    .state('app.loading', {
        url: '/loading',
        views: {
            'noMenuView': {
            templateUrl: 'templates/loading.html',
            controller: 'LoginCtrl'
            }
        }
    });
    
    $urlRouterProvider.otherwise('/app/login');
});



$('.form-part input').click(function(e){
    $(this).parents().addClass('new').siblings().removeClass('new');
    e.stopPropagation()
});